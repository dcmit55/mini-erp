<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Admin\Department;
use App\Models\Finance\KasbonRequest;
use App\Models\Finance\KasbonLimit;
use App\Models\Finance\KasbonAuditLog;
use App\Models\Hr\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class KasbonPublicController extends Controller
{
    public function create()
    {
        $departments = Department::orderBy('name')->get();
        $employees   = Employee::with('department')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'employee_no', 'department_id']);
        return view('finance.kasbon.public.form', compact('departments', 'employees'));
    }

    public function store(Request $request)
    {
        // Honeypot check
        if ($request->filled('website')) {
            return redirect()->route('kasbon.create');
        }

        $request->validate([
            'nama_lengkap' => 'required|string|min:3|max:100',
            'nik_karyawan' => 'required|string|max:30',
            'department_id' => 'required|exists:departments,id',
            'no_wa'         => ['required', 'string', 'min:10', 'max:20', 'regex:/^(\+62|08)\d{8,13}$/'],
            'jumlah'        => 'required|numeric|min:100000',
            'tenor_bulan'   => 'required|integer|in:1,2,3,6,12',
            'alasan'        => 'required|string|min:20',
            'dokumen'       => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'no_wa.regex'   => 'Format No. WhatsApp tidak valid (contoh: 08xxx atau +62xxx)',
            'jumlah.min'    => 'Jumlah kasbon minimal Rp 100.000',
            'alasan.min'    => 'Alasan minimal 20 karakter',
        ]);

        $nik  = $request->nik_karyawan;
        $deptId = $request->department_id;

        // Cek limit divisi
        $limit = KasbonLimit::where('department_id', $deptId)->where('is_active', true)->first();
        if ($limit) {
            if ($request->jumlah > $limit->max_amount) {
                return back()->withInput()->withErrors([
                    'jumlah' => 'Jumlah melebihi batas kasbon departemen (maks Rp ' . number_format($limit->max_amount, 0, ',', '.') . ')',
                ]);
            }
            if ($request->tenor_bulan > $limit->max_tenor) {
                return back()->withInput()->withErrors([
                    'tenor_bulan' => 'Tenor melebihi batas departemen (maks ' . $limit->max_tenor . ' bulan)',
                ]);
            }
        }

        // Cek cooldown & kasbon aktif
        $activeCount = KasbonRequest::where('nik_karyawan', $nik)
            ->whereIn('status', KasbonRequest::ACTIVE_STATUSES)
            ->count();

        $maxActive = $limit ? $limit->max_active : 1;
        if ($activeCount >= $maxActive) {
            return back()->withInput()->withErrors([
                'nik_karyawan' => 'Anda masih memiliki kasbon aktif yang belum lunas.',
            ]);
        }

        $cooldownDays = $limit ? $limit->cooldown_days : 7;
        $recentPending = KasbonRequest::where('nik_karyawan', $nik)
            ->whereIn('status', ['pending', 'under_review'])
            ->where('submitted_at', '>=', Carbon::now()->subDays($cooldownDays))
            ->exists();

        if ($recentPending) {
            return back()->withInput()->withErrors([
                'nik_karyawan' => 'Anda sudah memiliki pengajuan kasbon dalam ' . $cooldownDays . ' hari terakhir.',
            ]);
        }

        // Generate ref number
        $today    = Carbon::now()->format('Ymd');
        $lastSeq  = KasbonRequest::whereDate('submitted_at', Carbon::today())->count() + 1;
        $refNumber = 'KSB-' . $today . '-' . str_pad($lastSeq, 4, '0', STR_PAD_LEFT);

        // Generate token
        $token = hash('sha256', $refNumber . $nik . now()->timestamp . config('app.key'));

        // Upload dokumen langsung ke public/
        $dokumenUrl = null;
        if ($request->hasFile('dokumen')) {
            $file     = $request->file('dokumen');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('kasbon/dokumen'), $filename);
            $dokumenUrl = 'kasbon/dokumen/' . $filename;
        }

        // Soft-link employee
        $employee = Employee::where('employee_no', 'like', '%' . ltrim($nik, '0') . '%')->first();

        $kasbon = KasbonRequest::create([
            'ref_number'    => $refNumber,
            'employee_id'   => $employee?->id,
            'nama_lengkap'  => $request->nama_lengkap,
            'nik_karyawan'  => $nik,
            'department_id' => $deptId,
            'no_wa'         => $request->no_wa,
            'jumlah_diminta' => $request->jumlah,
            'tenor_bulan'   => $request->tenor_bulan,
            'alasan'        => $request->alasan,
            'dokumen_url'   => $dokumenUrl,
            'status'        => KasbonRequest::STATUS_PENDING,
            'token'         => $token,
            'ip_address'    => $request->ip(),
            'submitted_at'  => now(),
        ]);

        // Audit log
        KasbonAuditLog::create([
            'kasbon_id'  => $kasbon->id,
            'action'     => 'submitted',
            'from_status' => null,
            'to_status'  => KasbonRequest::STATUS_PENDING,
            'actor_type' => 'system',
            'note'       => 'Pengajuan kasbon dikirim dari form publik',
            'created_at' => now(),
        ]);

        return redirect()->route('kasbon.create')
            ->with('kasbon_success', [
                'ref_number' => $refNumber,
                'token'      => $token,
            ]);
    }

    public function status(Request $request)
    {
        $ref   = $request->query('ref');
        $kasbon = null;

        if ($ref) {
            $kasbon = KasbonRequest::with(['installments', 'department'])
                ->where('ref_number', $ref)
                ->first();
        }

        return view('finance.kasbon.public.status', compact('kasbon', 'ref'));
    }
}
