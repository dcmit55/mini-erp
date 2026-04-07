<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Admin\Department;
use App\Models\Finance\KasbonRequest;
use App\Models\Finance\KasbonInstallment;
use App\Models\Finance\KasbonAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class KasbonAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = KasbonRequest::with('department')->latest('submitted_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('nama_lengkap', 'like', "%$q%")
                    ->orWhere('nik_karyawan', 'like', "%$q%")
                    ->orWhere('ref_number', 'like', "%$q%");
            });
        }

        $kasbons     = $query->paginate(20)->withQueryString();
        $departments = Department::orderBy('name')->get();

        $summary = [
            'pending'     => KasbonRequest::where('status', 'pending')->count(),
            'active'      => KasbonRequest::whereIn('status', KasbonRequest::ACTIVE_STATUSES)->count(),
            'outstanding' => KasbonRequest::whereIn('status', ['disbursed', 'repaying'])->sum('jumlah_disetujui'),
        ];

        return view('finance.kasbon.admin.index', compact('kasbons', 'departments', 'summary'));
    }

    public function installments(Request $request)
    {
        $month       = $request->input('month', Carbon::now()->format('Y-m'));
        $departmentId = $request->input('department_id');
        $status      = $request->input('status', 'unpaid');

        [$year, $mon] = explode('-', $month);

        $query = KasbonInstallment::with(['kasbon.department'])
            ->whereHas('kasbon', function ($q) {
                $q->whereIn('status', ['disbursed', 'repaying']);
            })
            ->whereYear('due_date', $year)
            ->whereMonth('due_date', $mon);

        if ($status === 'unpaid') {
            $query->whereIn('status', ['pending', 'partial']);
        } elseif ($status === 'paid') {
            $query->where('status', 'paid');
        }

        if ($departmentId) {
            $query->whereHas('kasbon', fn($q) => $q->where('department_id', $departmentId));
        }

        $installments = $query->orderBy('due_date')->paginate(25)->withQueryString();
        $departments  = Department::orderBy('name')->get();

        $summary = [
            'unpaid'       => KasbonInstallment::whereHas('kasbon', fn($q) => $q->whereIn('status', ['disbursed', 'repaying']))
                ->whereYear('due_date', $year)->whereMonth('due_date', $mon)
                ->whereIn('status', ['pending', 'partial'])->count(),
            'paid'         => KasbonInstallment::whereHas('kasbon', fn($q) => $q->whereIn('status', ['disbursed', 'repaying']))
                ->whereYear('due_date', $year)->whereMonth('due_date', $mon)
                ->where('status', 'paid')->count(),
            'total_unpaid' => KasbonInstallment::whereHas('kasbon', fn($q) => $q->whereIn('status', ['disbursed', 'repaying']))
                ->whereYear('due_date', $year)->whereMonth('due_date', $mon)
                ->whereIn('status', ['pending', 'partial'])->sum('jumlah_cicilan'),
        ];

        return view('finance.kasbon.admin.installments', compact(
            'installments', 'departments', 'summary', 'month', 'status', 'departmentId'
        ));
    }

    public function show($id)
    {
        $kasbon = KasbonRequest::with([
            'department',
            'reviewer',
            'installments.creator',
            'auditLogs.actor',
        ])->findOrFail($id);

        return view('finance.kasbon.admin.show', compact('kasbon'));
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'jumlah_disetujui' => 'required|numeric|min:100000',
            'tenor_bulan'      => 'required|integer|in:1,2,3,6,12',
            'catatan_admin'    => 'nullable|string|max:500',
        ]);

        $kasbon = KasbonRequest::findOrFail($id);

        if (!in_array($kasbon->status, ['pending', 'under_review'])) {
            return back()->with('error', 'Pengajuan ini sudah tidak bisa di-approve.');
        }

        $fromStatus = $kasbon->status;
        $kasbon->update([
            'status'           => KasbonRequest::STATUS_APPROVED,
            'jumlah_disetujui' => $request->jumlah_disetujui,
            'tenor_bulan'      => $request->tenor_bulan,
            'catatan_admin'    => $request->catatan_admin,
            'reviewed_at'      => now(),
            'reviewed_by'      => Auth::id(),
        ]);

        // Generate jadwal cicilan
        $this->generateInstallments($kasbon);

        KasbonAuditLog::create([
            'kasbon_id'   => $kasbon->id,
            'action'      => 'approved',
            'from_status' => $fromStatus,
            'to_status'   => KasbonRequest::STATUS_APPROVED,
            'actor_id'    => Auth::id(),
            'actor_type'  => 'admin',
            'note'        => $request->catatan_admin,
            'created_at'  => now(),
        ]);

        return redirect()->route('kasbon.admin.show', $kasbon->id)
            ->with('success', 'Kasbon berhasil disetujui. Jadwal cicilan telah dibuat.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'catatan_admin' => 'required|string|min:5|max:500',
        ]);

        $kasbon = KasbonRequest::findOrFail($id);

        if (!in_array($kasbon->status, ['pending', 'under_review'])) {
            return back()->with('error', 'Pengajuan ini sudah tidak bisa di-reject.');
        }

        $fromStatus = $kasbon->status;
        $kasbon->update([
            'status'        => KasbonRequest::STATUS_REJECTED,
            'catatan_admin' => $request->catatan_admin,
            'reviewed_at'   => now(),
            'reviewed_by'   => Auth::id(),
        ]);

        KasbonAuditLog::create([
            'kasbon_id'   => $kasbon->id,
            'action'      => 'rejected',
            'from_status' => $fromStatus,
            'to_status'   => KasbonRequest::STATUS_REJECTED,
            'actor_id'    => Auth::id(),
            'actor_type'  => 'admin',
            'note'        => $request->catatan_admin,
            'created_at'  => now(),
        ]);

        return redirect()->route('kasbon.admin.show', $kasbon->id)
            ->with('success', 'Kasbon berhasil ditolak.');
    }

    public function disburse(Request $request, $id)
    {
        $kasbon = KasbonRequest::findOrFail($id);

        if ($kasbon->status !== KasbonRequest::STATUS_APPROVED) {
            return back()->with('error', 'Hanya kasbon berstatus APPROVED yang bisa dicairkan.');
        }

        $kasbon->update([
            'status'       => KasbonRequest::STATUS_DISBURSED,
            'disbursed_at' => now(),
        ]);

        // Update due_date cicilan relatif dari tanggal cair
        $kasbon->installments->each(function ($cicilan) use ($kasbon) {
            $cicilan->update([
                'due_date' => Carbon::parse($kasbon->disbursed_at)->addMonths($cicilan->bulan_ke),
            ]);
        });

        KasbonAuditLog::create([
            'kasbon_id'   => $kasbon->id,
            'action'      => 'disbursed',
            'from_status' => KasbonRequest::STATUS_APPROVED,
            'to_status'   => KasbonRequest::STATUS_DISBURSED,
            'actor_id'    => Auth::id(),
            'actor_type'  => 'admin',
            'note'        => 'Dana dicairkan pada ' . now()->format('d M Y'),
            'created_at'  => now(),
        ]);

        return redirect()->route('kasbon.admin.show', $kasbon->id)
            ->with('success', 'Dana kasbon berhasil dicatat sebagai cair.');
    }

    public function payInstallment(Request $request, $id, $installmentId)
    {
        $request->validate([
            'metode' => 'required|in:cash,payroll_deduction',
            'note'   => 'nullable|string|max:255',
        ]);

        $kasbon  = KasbonRequest::findOrFail($id);
        $cicilan = KasbonInstallment::where('kasbon_id', $id)->findOrFail($installmentId);

        if ($cicilan->status === 'paid') {
            return back()->with('error', 'Cicilan ini sudah lunas.');
        }

        $cicilan->update([
            'jumlah_dibayar' => $cicilan->jumlah_cicilan,
            'status'         => KasbonInstallment::STATUS_PAID,
            'metode'         => $request->metode,
            'paid_at'        => now(),
            'created_by'     => Auth::id(),
            'note'           => $request->note,
        ]);

        // Cek apakah semua cicilan lunas → auto settle
        $allPaid = $kasbon->installments()->where('status', '!=', 'paid')->doesntExist();
        if ($allPaid) {
            $kasbon->update([
                'status'     => KasbonRequest::STATUS_SETTLED,
                'settled_at' => now(),
            ]);
            KasbonAuditLog::create([
                'kasbon_id'   => $kasbon->id,
                'action'      => 'settled',
                'from_status' => KasbonRequest::STATUS_REPAYING,
                'to_status'   => KasbonRequest::STATUS_SETTLED,
                'actor_id'    => Auth::id(),
                'actor_type'  => 'system',
                'note'        => 'Semua cicilan lunas — kasbon otomatis SETTLED',
                'created_at'  => now(),
            ]);
        } elseif ($kasbon->status === KasbonRequest::STATUS_DISBURSED) {
            $kasbon->update(['status' => KasbonRequest::STATUS_REPAYING]);
            KasbonAuditLog::create([
                'kasbon_id'   => $kasbon->id,
                'action'      => 'repaying',
                'from_status' => KasbonRequest::STATUS_DISBURSED,
                'to_status'   => KasbonRequest::STATUS_REPAYING,
                'actor_id'    => Auth::id(),
                'actor_type'  => 'system',
                'note'        => 'Cicilan pertama dicatat — status berubah ke REPAYING',
                'created_at'  => now(),
            ]);
        }

        return redirect()->route('kasbon.admin.show', $kasbon->id)
            ->with('success', 'Pembayaran cicilan bulan ke-' . $cicilan->bulan_ke . ' berhasil dicatat.');
    }

    private function generateInstallments(KasbonRequest $kasbon): void
    {
        $kasbon->installments()->delete();

        $jumlah = $kasbon->jumlah_disetujui;
        $tenor  = $kasbon->tenor_bulan;
        $perBulan = (int) ceil($jumlah / $tenor);
        $lastAmount = $jumlah - ($perBulan * ($tenor - 1));

        for ($i = 1; $i <= $tenor; $i++) {
            KasbonInstallment::create([
                'kasbon_id'      => $kasbon->id,
                'bulan_ke'       => $i,
                'due_date'       => Carbon::now()->addMonths($i),
                'jumlah_cicilan' => $i === $tenor ? $lastAmount : $perBulan,
                'jumlah_dibayar' => 0,
                'status'         => KasbonInstallment::STATUS_PENDING,
                'created_by'     => Auth::id(),
            ]);
        }
    }
}
