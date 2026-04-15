<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\WarningBatch;
use App\Models\Hr\ViolationCategory;
use App\Models\Hr\Employee;
use App\Services\WarningLetterService;
use Illuminate\Http\Request;

class WarningBatchController extends Controller
{
    public function __construct(
        private WarningLetterService $wlService,
    ) {}

    public function index()
    {
        $batches = WarningBatch::with(['violationCategory', 'creator', 'warningLetters'])
            ->latest()
            ->paginate(15);

        return view('hr.warning-batches.index', compact('batches'));
    }

    public function create()
    {
        $violationCategories = ViolationCategory::bulkIssuable()->orderBy('name')->get();
        $employees           = Employee::select('id', 'employee_no', 'name')->orderBy('name')->get();

        return view('hr.warning-batches.create', compact('violationCategories', 'employees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_name'           => 'required|string|max:100',
            'incident_description' => 'required|string|min:10',
            'violation_cat_id'     => 'required|exists:violation_categories,id',
            'incident_date'        => 'required|date|before_or_equal:today',
            'employee_ids'         => 'required|array|min:2',
            'employee_ids.*'       => 'exists:employees,id',
            'evidence_path'        => 'nullable|string',
        ]);

        $result = $this->wlService->createBulk(
            array_merge($validated, ['created_by' => auth()->id()]),
            $validated['employee_ids']
        );

        $batch   = $result['batch'];
        $created = count($result['letters']);
        $skipped = count($result['skipped']);

        $msg = "{$created} surat peringatan berhasil dibuat dalam batch.";
        if ($skipped > 0) {
            $msg .= " {$skipped} karyawan di-skip (SP4 aktif).";
        }

        return redirect()->route('warning-batches.show', $batch)->with('success', $msg);
    }

    public function show(WarningBatch $warningBatch)
    {
        $warningBatch->load([
            'violationCategory',
            'creator',
            'warningLetters.employee.department',
            'warningLetters.violationCategory',
        ]);

        return view('hr.warning-batches.show', compact('warningBatch'));
    }
}
