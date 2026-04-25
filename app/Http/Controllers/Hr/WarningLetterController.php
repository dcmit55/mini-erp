<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\WarningLetter;
use App\Models\Hr\WarningTemplate;
use App\Models\Hr\ViolationCategory;
use App\Models\Hr\WarningLetterAcknowledgment;
use App\Services\WarningLetterService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Carbon\Carbon;

class WarningLetterController extends Controller
{
    public function __construct(
        private WarningLetterService $wlService,
    ) {}

    // ─── CRUD ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = WarningLetter::with(['employee.department', 'violationCategory', 'creator'])
            ->orderByDesc('created_at');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('sp_level')) {
            $query->where('sp_level', $request->sp_level);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('issued_date', [$request->start_date, $request->end_date]);
        }

        $letters   = $query->paginate(15)->withQueryString();
        $employees = Employee::select('id', 'employee_no', 'name')->orderBy('name')->get();

        $stats = [
            'total_active'  => WarningLetter::active()->count(),
            'sp3_active'    => WarningLetter::active()->where('sp_level', 3)->count(),
            'expiring_soon' => WarningLetter::active()
                                ->where('valid_until', '<=', now()->addDays(14)->toDateString())
                                ->count(),
        ];

        return view('hr.warning-letters.index', compact('letters', 'employees', 'stats'));
    }

    public function create(Request $request)
    {
        $employees           = Employee::select('id', 'employee_no', 'name')->orderBy('name')->get();
        $violationCategories = ViolationCategory::active()->orderBy('name')->get();
        $templates           = WarningTemplate::active()->get();

        $suggestedSpLevel = null;
        $activeSpLevel    = null;
        $terminationFlag  = false;

        if ($request->filled('employee_id')) {
            try {
                $suggestedSpLevel = $this->wlService->determineSpLevel((int) $request->employee_id);
                $activeSpLevel    = $this->wlService->getActiveSpLevel((int) $request->employee_id);
            } catch (\RuntimeException $e) {
                $terminationFlag = true;
            }
        }

        return view('hr.warning-letters.create', compact(
            'employees', 'violationCategories', 'templates',
            'suggestedSpLevel', 'activeSpLevel', 'terminationFlag'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'letter_number'    => 'required|string|max:120|unique:warning_letters,letter_number',
            'employee_id'      => 'required|exists:employees,id',
            'violation_cat_id' => 'required|exists:violation_categories,id',
            'violation_date'   => 'required|date|before_or_equal:today',
            'reason'           => 'required|string|min:10',
            'template_id'      => 'nullable|exists:warning_templates,id',
        ], [
            'letter_number.required' => 'Nomor surat wajib diisi.',
            'letter_number.unique'   => 'Nomor surat ini sudah digunakan.',
        ]);

        // Verifikasi SP level yang terbaca dari nomor surat
        $detectedSpLevel = $this->wlService->detectSpLevelFromLetterNumber($validated['letter_number']);
        if ($detectedSpLevel === null) {
            return back()->withInput()->with('error',
                'SP level tidak terdeteksi dari nomor surat. Pastikan nomor surat mengandung "SP1", "SP2", atau "SP3".'
            );
        }

        try {
            $letter = $this->wlService->createSingle(array_merge($validated, [
                'created_by' => auth()->id(),
                'sp_level'   => $detectedSpLevel,
            ]));
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('warning-letters.show', $letter)
            ->with('success', "Surat Peringatan {$letter->spLabel} berhasil dibuat.");
    }

    public function show(WarningLetter $warningLetter)
    {
        $warningLetter->load([
            'employee.department',
            'violationCategory',
            'template',
            'batch',
            'creator',
            'acknowledgment.witness',
        ]);

        return view('hr.warning-letters.show', compact('warningLetter'));
    }

    public function edit(WarningLetter $warningLetter)
    {
        if (!$warningLetter->isEditable()) {
            return redirect()->route('warning-letters.show', $warningLetter)
                ->with('error', 'Hanya letter berstatus draft yang dapat diedit.');
        }

        $employees           = Employee::select('id', 'employee_no', 'name')->orderBy('name')->get();
        $violationCategories = ViolationCategory::active()->orderBy('name')->get();
        $templates           = WarningTemplate::active()->get();

        return view('hr.warning-letters.edit', compact(
            'warningLetter', 'employees', 'violationCategories', 'templates'
        ));
    }

    public function update(Request $request, WarningLetter $warningLetter)
    {
        if (!$warningLetter->isEditable()) {
            return redirect()->route('warning-letters.show', $warningLetter)
                ->with('error', 'Hanya letter berstatus draft yang dapat diedit.');
        }

        $validated = $request->validate([
            'letter_number'    => 'required|string|max:120|unique:warning_letters,letter_number,' . $warningLetter->id,
            'violation_cat_id' => 'required|exists:violation_categories,id',
            'violation_date'   => 'required|date|before_or_equal:today',
            'reason'           => 'required|string|min:10',
            'template_id'      => 'nullable|exists:warning_templates,id',
        ], [
            'letter_number.required' => 'Nomor surat wajib diisi.',
            'letter_number.unique'   => 'Nomor surat ini sudah digunakan.',
        ]);

        // Sinkronkan sp_level dengan nomor surat yang diperbarui
        $detectedSpLevel = $this->wlService->detectSpLevelFromLetterNumber($validated['letter_number']);
        if ($detectedSpLevel === null) {
            return back()->withInput()->with('error',
                'SP level tidak terdeteksi dari nomor surat. Pastikan nomor surat mengandung "SP1", "SP2", atau "SP3".'
            );
        }

        $warningLetter->update(array_merge($validated, ['sp_level' => $detectedSpLevel]));

        return redirect()->route('warning-letters.show', $warningLetter)
            ->with('success', 'Draft berhasil diperbarui.');
    }

    public function destroy(WarningLetter $warningLetter)
    {
        if (!$warningLetter->isEditable()) {
            return back()->with('error', 'Hanya draft yang dapat dihapus.');
        }
        $warningLetter->delete();
        return redirect()->route('warning-letters.index')
            ->with('success', 'Draft warning letter dihapus.');
    }

    // ─── Dashboard ───────────────────────────────────────────────────────────

    public function dashboard()
    {
        $stats = [
            'total_active'  => WarningLetter::active()->count(),
            'sp1'           => WarningLetter::active()->where('sp_level', 1)->count(),
            'sp2'           => WarningLetter::active()->where('sp_level', 2)->count(),
            'sp3'           => WarningLetter::active()->where('sp_level', 3)->count(),
            'expiring_soon' => WarningLetter::active()
                                ->where('valid_until', '<=', now()->addDays(14)->toDateString())
                                ->count(),
            'total_batches' => \App\Models\Hr\WarningBatch::count(),
        ];

        $recentLetters = WarningLetter::with(['employee.department', 'violationCategory'])
            ->active()
            ->latest()
            ->limit(10)
            ->get();

        $expiringSoon = WarningLetter::with(['employee.department'])
            ->active()
            ->where('valid_until', '<=', now()->addDays(30)->toDateString())
            ->orderBy('valid_until')
            ->limit(10)
            ->get();

        return view('hr.warning-letters.dashboard', compact('stats', 'recentLetters', 'expiringSoon'));
    }

    // ─── Finalize (draft → approved, HR langsung approve) ────────────────────

    public function approve(WarningLetter $warningLetter)
    {
        if ($warningLetter->status !== 'draft') {
            return back()->with('error', 'Hanya letter berstatus draft yang dapat difinalisasi.');
        }

        $warningLetter->update(['status' => 'approved']);

        return back()->with('success', 'Surat Peringatan telah difinalisasi dan berlaku.' .
            ($warningLetter->sp_level === 3 ? ' SP3 adalah peringatan terakhir — gunakan tombol Terminate untuk memproses PHK.' : ''));
    }

    // ─── Acknowledgment ──────────────────────────────────────────────────────

    public function acknowledge(Request $request, WarningLetter $warningLetter)
    {
        if ($warningLetter->status !== 'approved') {
            return back()->with('error', 'Hanya letter yang sudah disetujui yang dapat di-acknowledge.');
        }
        if ($warningLetter->acknowledgment) {
            return back()->with('error', 'Letter ini sudah pernah di-acknowledge.');
        }

        WarningLetterAcknowledgment::create([
            'warning_letter_id' => $warningLetter->id,
            'employee_id'       => $warningLetter->employee_id,
            'acknowledged_at'   => now(),
            'method'            => 'digital',
            'witness_id'        => auth()->id(),
        ]);

        $warningLetter->update(['status' => 'acknowledged']);

        return back()->with('success', 'Karyawan telah menerima dan mengakui surat peringatan ini.');
    }

    // ─── PDF ─────────────────────────────────────────────────────────────────

    public function pdf(WarningLetter $warningLetter)
    {
        $warningLetter->load(['employee.department', 'violationCategory', 'template']);

        $data = [
            'letter'             => $warningLetter,
            'company_name'       => config('app.company_name', 'PT. DECOR CREATIVE MOMENTUM'),
            'company_address'    => config('app.company_address', ''),
            'letter_number'      => $warningLetter->letter_number,
            'employee_name'      => $warningLetter->employee->name,
            'employee_position'  => $warningLetter->employee->position,
            'department_name'    => $warningLetter->employee->department?->name ?? '-',
            'violation_category' => $warningLetter->violationCategory->name,
            'violation_date'     => $warningLetter->violation_date->format('d F Y'),
            'reason'             => $warningLetter->reason,
            'issued_date'        => $warningLetter->issued_date?->format('d F Y') ?? '-',
            'valid_until'        => $warningLetter->valid_until?->format('d F Y') ?? '-',
        ];

        $pdf = Pdf::loadView('hr.warning-letters.warning_letter', $data)
            ->setPaper('a4', 'portrait');

        $filename = "SP{$warningLetter->sp_level}_{$warningLetter->employee->employee_no}_{$warningLetter->issued_date?->format('Ymd')}.pdf";

        return $pdf->download($filename);
    }

    // ─── SP3 Termination ─────────────────────────────────────────────────────

    public function terminateEmployee(WarningLetter $warningLetter)
    {
        if ($warningLetter->sp_level !== 3) {
            return back()->with('error', 'Terminasi hanya dapat dilakukan melalui SP3.');
        }
        if (!in_array($warningLetter->status, ['approved', 'acknowledged'])) {
            return back()->with('error', 'Surat peringatan harus sudah difinalisasi sebelum terminasi.');
        }

        $employee = $warningLetter->employee;
        if ($employee->status === 'terminated') {
            return back()->with('error', 'Karyawan sudah berstatus terminated.');
        }

        $employee->update(['status' => 'terminated']);

        return back()->with('success', "Karyawan {$employee->name} telah di-terminate berdasarkan {$warningLetter->spLabel}.");
    }

}
