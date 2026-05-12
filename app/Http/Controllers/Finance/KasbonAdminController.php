<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Admin\Department;
use App\Models\Finance\KasbonRequest;
use App\Models\Finance\KasbonInstallment;
use App\Models\Finance\KasbonAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class KasbonAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:finance.kasbon.view');
    }

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
            'jumlah_disetujui'  => 'required|numeric|min:100000',
            'tenor_bulan'       => 'required|integer|in:1,2,3,6,12',
            'suku_bunga_persen' => 'required|numeric|min:0|max:100',
            'biaya_admin'       => 'required|numeric|min:0',
            'catatan_admin'     => 'nullable|string|max:500',
        ]);

        $kasbon = KasbonRequest::findOrFail($id);

        if (!in_array($kasbon->status, ['pending', 'under_review'])) {
            return back()->with('error', 'Pengajuan ini sudah tidak bisa di-approve.');
        }

        if ($request->jumlah_disetujui > $kasbon->jumlah_diminta) {
            return back()->withErrors(['jumlah_disetujui' => 'Jumlah disetujui tidak boleh melebihi jumlah diminta (Rp ' . number_format($kasbon->jumlah_diminta, 0, ',', '.') . ')']);
        }

        $fromStatus = $kasbon->status;
        $kasbon->update([
            'status'            => KasbonRequest::STATUS_APPROVED,
            'jumlah_disetujui'  => $request->jumlah_disetujui,
            'tenor_bulan'       => $request->tenor_bulan,
            'suku_bunga_persen' => $request->suku_bunga_persen,
            'biaya_admin'       => $request->biaya_admin,
            'catatan_admin'     => $request->catatan_admin,
            'reviewed_at'       => now(),
            'reviewed_by'       => Auth::id(),
        ]);

        $this->generateInstallments($kasbon);

        $tanpaBunga = (float) $request->suku_bunga_persen === 0.0;
        $auditNote  = $request->catatan_admin;
        if ($tanpaBunga) {
            $auditNote = trim('[Tanpa Bunga] ' . $auditNote);
        }

        KasbonAuditLog::create([
            'kasbon_id'   => $kasbon->id,
            'action'      => 'approved',
            'from_status' => $fromStatus,
            'to_status'   => KasbonRequest::STATUS_APPROVED,
            'actor_id'    => Auth::id(),
            'actor_type'  => 'admin',
            'note'        => $auditNote,
            'created_at'  => now(),
        ]);

        $successMsg = $tanpaBunga
            ? 'Kasbon disetujui <strong>tanpa bunga</strong>. Jadwal cicilan telah dibuat.'
            : 'Kasbon berhasil disetujui. Jadwal cicilan telah dibuat.';

        return redirect()->route('kasbon.admin.show', $kasbon->id)
            ->with('success', $successMsg);
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

        // Regenerate ulang jadwal cicilan dengan due_date berdasarkan tanggal cair
        $this->generateInstallments($kasbon);

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

    /**
     * Mencatat pembayaran cicilan (pokok + cash) sekaligus
     */
    public function payInstallment(Request $request, $id, $installmentId)
    {
        $request->validate([
            'metode_pokok' => 'required|in:payroll_deduction,pending',
            'metode_cash'  => 'required|in:cash,pending',
            'note'         => 'nullable|string|max:255',
        ]);

        $kasbon  = KasbonRequest::findOrFail($id);
        $cicilan = KasbonInstallment::where('kasbon_id', $id)->findOrFail($installmentId);

        if ($cicilan->status === 'paid') {
            return back()->with('error', 'Cicilan ini sudah lunas.');
        }

        $updates = [];
        $totalDibayar = (float) $cicilan->jumlah_dibayar;

        // Proses pembayaran pokok (potong gaji)
        if ($request->metode_pokok === 'payroll_deduction' && !$cicilan->pokok_paid_at) {
            $updates['pokok_paid_at'] = now();
            $updates['pokok_confirmed_by'] = Auth::id();
            $totalDibayar += (float) $cicilan->jumlah_pokok;
        }

        // Proses pembayaran cash (bunga + admin)
        if ($request->metode_cash === 'cash' && !$cicilan->cash_paid_at) {
            $cashAmount = (float) $cicilan->jumlah_bunga + (float) $cicilan->jumlah_biaya_admin;
            $updates['cash_paid_at'] = now();
            $updates['cash_received_by'] = Auth::id();
            $totalDibayar += $cashAmount;
        }

        if ($request->filled('note')) {
            $updates['note'] = $request->note;
        }

        $updates['jumlah_dibayar'] = $totalDibayar;
        $updates['created_by'] = Auth::id();

        $targetTotal = (float) $cicilan->jumlah_pokok + (float) $cicilan->jumlah_bunga + (float) $cicilan->jumlah_biaya_admin;
        
        if ($totalDibayar >= $targetTotal) {
            $updates['status'] = KasbonInstallment::STATUS_PAID;
            $updates['paid_at'] = now();
        } elseif ($totalDibayar > 0) {
            $updates['status'] = KasbonInstallment::STATUS_PARTIAL;
        }

        $cicilan->update($updates);
        $this->checkAndSettleKasbon($kasbon);

        return redirect()->route('kasbon.admin.show', $kasbon->id)
            ->with('success', 'Cicilan bulan ke-' . $cicilan->bulan_ke . ' berhasil dicatat.');
    }

    /**
     * Khusus mencatat pembayaran pokok (potong gaji)
     */
    public function confirmPokokRoute(Request $request, $id, $installmentId)
    {
        $request->validate(['note' => 'nullable|string|max:255']);
        $kasbon  = KasbonRequest::findOrFail($id);
        $cicilan = KasbonInstallment::where('kasbon_id', $id)->findOrFail($installmentId);

        if ($cicilan->pokok_paid_at) {
            return back()->with('error', 'Pokok cicilan ini sudah dikonfirmasi.');
        }

        $totalDibayar = (float) $cicilan->jumlah_dibayar + (float) $cicilan->jumlah_pokok;
        $targetTotal = (float) $cicilan->jumlah_pokok + (float) $cicilan->jumlah_bunga + (float) $cicilan->jumlah_biaya_admin;
        
        $updates = [
            'pokok_paid_at' => now(),
            'pokok_confirmed_by' => Auth::id(),
            'jumlah_dibayar' => $totalDibayar,
            'created_by' => Auth::id(),
        ];

        if ($request->filled('note')) {
            $updates['note'] = $request->note;
        }

        if ($totalDibayar >= $targetTotal) {
            $updates['status'] = KasbonInstallment::STATUS_PAID;
            $updates['paid_at'] = now();
        } elseif ($totalDibayar > 0) {
            $updates['status'] = KasbonInstallment::STATUS_PARTIAL;
        }

        $cicilan->update($updates);
        $this->checkAndSettleKasbon($kasbon);

        return redirect()->route('kasbon.admin.show', $kasbon->id)
            ->with('success', 'Pokok cicilan bulan ke-' . $cicilan->bulan_ke . ' dikonfirmasi sebagai potong gaji.');
    }

    /**
     * Khusus mencatat pembayaran cash (bunga + admin)
     */
    public function confirmCashRoute(Request $request, $id, $installmentId)
    {
        $request->validate(['note' => 'nullable|string|max:255']);
        $kasbon  = KasbonRequest::findOrFail($id);
        $cicilan = KasbonInstallment::where('kasbon_id', $id)->findOrFail($installmentId);

        if ($cicilan->cash_paid_at) {
            return back()->with('error', 'Pembayaran cash cicilan ini sudah dikonfirmasi.');
        }

        $cashAmount = (float) $cicilan->jumlah_bunga + (float) $cicilan->jumlah_biaya_admin;
        $totalDibayar = (float) $cicilan->jumlah_dibayar + $cashAmount;
        $targetTotal = (float) $cicilan->jumlah_pokok + (float) $cicilan->jumlah_bunga + (float) $cicilan->jumlah_biaya_admin;
        
        $updates = [
            'cash_paid_at' => now(),
            'cash_received_by' => Auth::id(),
            'jumlah_dibayar' => $totalDibayar,
            'created_by' => Auth::id(),
        ];

        if ($request->filled('note')) {
            $updates['note'] = $request->note;
        }

        if ($totalDibayar >= $targetTotal) {
            $updates['status'] = KasbonInstallment::STATUS_PAID;
            $updates['paid_at'] = now();
        } elseif ($totalDibayar > 0) {
            $updates['status'] = KasbonInstallment::STATUS_PARTIAL;
        }

        $cicilan->update($updates);
        $this->checkAndSettleKasbon($kasbon);

        return redirect()->route('kasbon.admin.show', $kasbon->id)
            ->with('success', 'Cash (bunga + admin) bulan ke-' . $cicilan->bulan_ke . ' berhasil diterima.');
    }

    /**
     * Cek apakah semua cicilan sudah lunas
     */
    private function checkAndSettleKasbon(KasbonRequest $kasbon): void
    {
        $kasbon->refresh();

        $allPaid = $kasbon->installments()->where('status', '!=', 'paid')->doesntExist();
        if ($allPaid) {
            $kasbon->update(['status' => KasbonRequest::STATUS_SETTLED, 'settled_at' => now()]);
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
        } elseif ($kasbon->status === KasbonRequest::STATUS_DISBURSED && $kasbon->installments()->where('status', '!=', 'pending')->exists()) {
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
    }

    /**
     * GENERATE JADWAL CICILAN - BUNGA EFEKTIF DARI SISA POKOK
     * 
     * Rumus yang BENAR:
     * - Pokok per bulan = Total Pokok / Tenor (dibulatkan ke bawah, bulan terakhir menyesuaikan)
     * - Bunga per bulan = SISA POKOK SAAT INI × (Suku Bunga / 100)
     * - Biaya admin = hanya bulan pertama
     * 
     * Skema Pembayaran:
     * - Pokok → via Potong Gaji
     * - Bunga + Admin → via Cash ke Finance
     * 
     * CONTOH:
     * Pinjaman Rp 300.000, Tenor 3 bulan, Bunga 2% per bulan, Admin Rp 50.000
     * 
     * Hasil:
     * Bulan 1: Sisa Pokok=300.000 → Bunga=6.000, Admin=50.000, Cash=56.000, Pokok=100.000
     * Bulan 2: Sisa Pokok=200.000 → Bunga=4.000, Admin=0, Cash=4.000, Pokok=100.000
     * Bulan 3: Sisa Pokok=100.000 → Bunga=2.000, Admin=0, Cash=2.000, Pokok=100.000
     */
    private function generateInstallments(KasbonRequest $kasbon): void
    {
        // Hapus cicilan lama jika ada
        $kasbon->installments()->delete();

        // Ambil data dari kasbon
        $totalPokok  = (float) $kasbon->jumlah_disetujui;
        $tenor       = (int)   $kasbon->tenor_bulan;
        $bungaRate   = (float) $kasbon->suku_bunga_persen / 100;
        $biayaAdmin  = (float) $kasbon->biaya_admin;

        // Hitung pokok per bulan (FLAT dengan pembulatan ke bawah)
        $pokokPerBulan = (int) floor($totalPokok / $tenor);
        $pokokTerakhir = $totalPokok - ($pokokPerBulan * ($tenor - 1));

        // Tentukan tanggal dasar untuk due_date
        $baseDate = $kasbon->disbursed_at 
            ? Carbon::parse($kasbon->disbursed_at)
            : Carbon::now();

        // Inisialisasi sisa pokok untuk perhitungan bunga (INI PENTING!)
        $sisaPokok = $totalPokok;

        for ($bulanKe = 1; $bulanKe <= $tenor; $bulanKe++) {
            // HITUNG BUNGA DARI SISA POKOK SAAT INI (SEBELUM DIBAYAR)
            $bungaBulan = round($sisaPokok * $bungaRate, 2);

            // Tentukan pokok bulan ini
            $pokokBulan = ($bulanKe === $tenor) ? $pokokTerakhir : $pokokPerBulan;

            // Biaya admin hanya di bulan pertama
            $adminBulan = ($bulanKe === 1) ? $biayaAdmin : 0;

            // Total cicilan (referensi)
            $totalCicilan = $pokokBulan + $bungaBulan + $adminBulan;

            // Due date (jatuh tempo)
            $dueDate = $baseDate->copy()->addMonths($bulanKe);

            // Simpan ke database
            KasbonInstallment::create([
                'kasbon_id'          => $kasbon->id,
                'bulan_ke'           => $bulanKe,
                'due_date'           => $dueDate,
                'jumlah_pokok'       => $pokokBulan,      // via Potong Gaji
                'jumlah_bunga'       => $bungaBulan,      // via Cash
                'jumlah_biaya_admin' => $adminBulan,      // via Cash
                'jumlah_cicilan'     => $totalCicilan,    // total referensi
                'jumlah_dibayar'     => 0,
                'status'             => KasbonInstallment::STATUS_PENDING,
                'created_by'         => Auth::id(),
            ]);

            // KURANGI SISA POKOK UNTUK PERHITUNGAN BULAN DEPAN (INI KUNCI!)
            $sisaPokok -= $pokokBulan;
        }

        // Debug log untuk memverifikasi perhitungan
        Log::info('Generate Installments untuk Kasbon ID: ' . $kasbon->id, [
            'total_pokok' => $totalPokok,
            'tenor' => $tenor,
            'bunga_rate' => $bungaRate,
            'biaya_admin' => $biayaAdmin,
            'jumlah_cicilan' => $kasbon->installments()->count()
        ]);
    }
}