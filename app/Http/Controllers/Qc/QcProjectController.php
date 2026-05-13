<?php

namespace App\Http\Controllers\Qc;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Qc\QcPackingItem;
use App\Models\Qc\QcProject;
use App\Models\Production\JobOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class QcProjectController extends Controller
{
    // Item packing default per type
    const PACKING_MASCOT_REQUIRED = [
        'body mascot','body suit','body pad','shirt','cable','charger',
        'fan','battery','shoe','cover shoes','standy','handle',
    ];
    const PACKING_MASCOT_OPTIONAL = [
        'pants','dress','harness','hands','remote','tail',
    ];
    const PACKING_INFLATABLE = [
        'body suit','vest','body','magnetic expression','cover shoes',
        'battery','fan','charger','standy','handle',
    ];

    // Gradient presets (mirror prototype)
    const GRADIENTS = [
        'linear-gradient(135deg,#667eea,#764ba2)',
        'linear-gradient(135deg,#f093fb,#f5576c)',
        'linear-gradient(135deg,#4facfe,#00f2fe)',
        'linear-gradient(135deg,#43e97b,#38f9d7)',
        'linear-gradient(135deg,#fa709a,#fee140)',
        'linear-gradient(135deg,#a18cd1,#fbc2eb)',
        'linear-gradient(135deg,#fccb90,#d57eeb)',
        'linear-gradient(135deg,#fd7043,#ff8a65)',
    ];

    public function employees(Request $request): JsonResponse
    {
        $deptMap   = ['mascot' => 2, 'costume' => 1];
        $deptId    = $deptMap[$request->query('context', 'mascot')] ?? 2;

        $employees = Employee::where('department_id', $deptId)
            ->where('status', 'Active')
            ->orderBy('name')
            ->get(['employee_no', 'name', 'position']);

        return response()->json($employees->map(fn($e) => [
            'id'       => $e->employee_no,
            'name'     => $e->name,
            'position' => $e->position ?? null,
        ])->values());
    }

    public function availableJobOrders(Request $request): JsonResponse
    {
        $deptMap = ['mascot' => 2, 'costume' => 1];
        $deptId  = $deptMap[$request->query('context', 'mascot')] ?? 2;

        $jobOrders = JobOrder::with('project')
            ->where('department_id', $deptId)
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhereNot('status', 'Delivered');
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($jo) => [
                'id'           => $jo->id,
                'name'         => $jo->name,
                'project_id'   => $jo->project_id,
                'project_name' => $jo->project?->name,
                'status'       => $jo->status,
                'deadline'     => $jo->delivery_date ?? $jo->end_date,
            ]);

        return response()->json($jobOrders);
    }

    public function index(): JsonResponse
    {
        $projects = QcProject::with(['checklistItems', 'rejectLogs', 'creator', 'jobOrder.department', 'jobOrder.project'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($p) => $this->formatProject($p));

        return response()->json($projects);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'job_order_id'    => 'required|exists:job_orders,id',
            'mascot_type'     => 'required|string|max:60',
            'total_unit'      => 'required|integer|min:1',
            'inspection_date' => 'required|date',
            'deadline'        => 'nullable|date',
        ]);

        $jo = JobOrder::with('project')->findOrFail($data['job_order_id']);

        $isMascot = $jo->department_id == 2; // DCM Mascot only gets mascot packing items

        $project = QcProject::create([
            'job_order_id'    => $jo->id,
            'project_id'      => $jo->project_id,
            'job_number'      => $jo->id,
            'project_name'    => $jo->name,
            'mascot_type'     => $data['mascot_type'],
            'created_by'      => $request->user()->id,
            'inspection_date' => $data['inspection_date'],
            'deadline'        => $data['deadline'] ?? $jo->delivery_date ?? $jo->end_date,
            'total_unit'      => $data['total_unit'],
            'cover_gradient'  => Arr::random(self::GRADIENTS),
            'stage_progress'  => ['cutting' => 0, 'sewing' => 0, 'finishing' => 0],
        ]);

        // Seed mascot-specific packing items only for mascot department
        if ($isMascot) {
            $this->seedPackingItems($project);
        }

        $project->load(['checklistItems', 'rejectLogs', 'packingItems', 'creator', 'jobOrder.department', 'jobOrder.project']);

        return response()->json($this->formatProject($project), 201);
    }

    public function show(QcProject $project): JsonResponse
    {
        $project->load([
            'checklistItems.photos',
            'rejectLogs.photos',
            'packingItems.photos',
            'dailyProgress.items.photos',
            'creator',
            'jobOrder.department',
            'jobOrder.project',
        ]);

        return response()->json($this->formatProject($project, full: true));
    }

    public function destroy(QcProject $project): JsonResponse
    {
        $project->delete();

        return response()->json(['message' => 'Project deleted.']);
    }

    public function finalDecision(Request $request, QcProject $project): JsonResponse
    {
        $data = $request->validate([
            'result'    => 'required|in:PASS,FAIL',
            'grade'     => 'nullable|string|max:10',
            'decision'  => 'nullable|string',
            'inspector' => 'nullable|string|max:100',
            'manager'   => 'nullable|string|max:100',
            'note'      => 'nullable|string',
        ]);

        $project->update([
            'status'         => $data['result'] === 'PASS' ? 'Delivered' : 'Rejected',
            'final_decision' => array_merge($data, ['ts' => now()->toISOString()]),
        ]);

        return response()->json($this->formatProject($project));
    }

    public function addCustomPart(Request $request, QcProject $project): JsonResponse
    {
        $data = $request->validate([
            'part' => 'required|string|max:80',
        ]);

        $current = $project->custom_parts ?? [];
        if (!in_array($data['part'], $current)) {
            $current[] = $data['part'];
            $project->update(['custom_parts' => $current]);
        }

        return response()->json(['custom_parts' => $project->fresh()->custom_parts]);
    }

    // ── Private Helpers ────────────────────────────────────────────────

    private function seedPackingItems(QcProject $project): void
    {
        $items = $project->mascot_type === 'Compress Foam'
            ? array_merge(
                array_map(fn($n) => ['name' => $n, 'type' => 'required'], self::PACKING_MASCOT_REQUIRED),
                array_map(fn($n) => ['name' => $n, 'type' => 'optional'], self::PACKING_MASCOT_OPTIONAL),
              )
            : array_map(fn($n) => ['name' => $n, 'type' => 'required'], self::PACKING_INFLATABLE);

        foreach ($items as $i => $item) {
            QcPackingItem::create([
                'qc_project_id' => $project->id,
                'name'          => $item['name'],
                'type'          => $item['type'],
                'sort_order'    => $i,
            ]);
        }
    }

    private function formatProject(QcProject $p, bool $full = false): array
    {
        $base = [
            'id'              => $p->id,
            'uid'             => $p->uid,
            'job_order_id'    => $p->job_order_id,
            'project_id'      => $p->project_id,
            'job_number'           => $p->job_number,
            'project_name'         => $p->project_name,
            'actual_project_name'  => $p->jobOrder?->project?->name ?? $p->project_name,
            'department_name'      => $p->jobOrder?->department?->name ?? null,
            'mascot_type'          => $p->mascot_type,
            'created_by'           => $p->creator?->name ?? '—',
            'inspection_date' => $p->inspection_date?->toDateString(),
            'deadline'        => $p->deadline?->toDateString(),
            'total_unit'      => $p->total_unit,
            'status'          => $p->status,
            'progress'        => $p->progress,
            'cover_gradient'  => $p->cover_gradient,
            'cover_image_url' => $p->cover_image_path
                ? \Storage::disk('public')->url($p->cover_image_path)
                : null,
            'packing_verified' => $p->packing_verified,
            'final_decision'   => $p->final_decision,
            'custom_parts'     => $p->custom_parts ?? [],
            'packing_config'   => $p->packing_config ?? [],
            'stage_progress'   => $p->stage_progress ?? ['cutting' => 0, 'sewing' => 0, 'finishing' => 0],
            'created_at'       => $p->created_at->toDateString(),
            'checklist_pass'   => $p->relationLoaded('checklistItems')
                ? $p->checklistItems->where('status', 'PASS')->count() : 0,
            'checklist_fail'   => $p->relationLoaded('checklistItems')
                ? $p->checklistItems->where('status', 'FAIL')->count() : 0,
            'open_defects'     => $p->relationLoaded('rejectLogs')
                ? $p->rejectLogs->where('rework_status', 'OPEN')->count() : 0,
            'total_defects'    => $p->relationLoaded('rejectLogs')
                ? $p->rejectLogs->count() : 0,
        ];

        if ($full) {
            $base['checklist_items'] = $p->checklistItems->map(fn($ci) => [
                'id'         => $ci->id,
                'uid'        => $ci->uid,
                'section_id' => $ci->section_id,
                'item_id'    => $ci->item_id,
                'status'     => $ci->status,
                'note'       => $ci->note,
                'photos'     => $ci->photos->map(fn($ph) => [
                    'uid' => $ph->uid, 'url' => $ph->url,
                ]),
            ])->values();

            $base['reject_logs'] = $p->rejectLogs->map(fn($r) => [
                'id'                     => $r->id,
                'uid'                    => $r->uid,
                'source'                 => $r->source,
                'item_id'                => $r->item_id,
                'daily_item_id'          => $r->daily_item_id,
                'fail_date_str'          => $r->fail_date_str?->toDateString(),
                'item_name'              => $r->item_name,
                'defect_category'        => $r->defect_category,
                'severity'               => $r->severity,
                'fail_note'              => $r->fail_note,
                'fail_operator'          => $r->fail_operator,
                'rework_assigned_to'     => $r->rework_assigned_to,
                'target_completion_date' => $r->target_completion_date?->toDateString(),
                'rework_status'          => $r->rework_status,
                'closed_date'            => $r->closed_date?->toISOString(),
                'rework_history'         => $r->rework_history ?? [],
                'photos'                 => $r->photos->map(fn($ph) => [
                    'uid' => $ph->uid, 'url' => $ph->url,
                ]),
            ])->values();

            $base['packing_items'] = $p->packingItems->map(fn($pi) => [
                'id'         => $pi->id,
                'uid'        => $pi->uid,
                'name'       => $pi->name,
                'type'       => $pi->type,
                'is_checked' => $pi->is_checked,
                'is_hidden'  => $pi->is_hidden,
                'sort_order' => $pi->sort_order,
                'photos'     => $pi->photos->map(fn($ph) => [
                    'uid' => $ph->uid, 'url' => $ph->url,
                ]),
            ])->values();

            $base['daily_progress'] = $p->dailyProgress->map(fn($dp) => [
                'id'           => $dp->id,
                'uid'          => $dp->uid,
                'date'         => $dp->date->toDateString(),
                'session_note' => $dp->session_note,
                'operators'    => $dp->operators ?? [],
                'items'        => $dp->items->map(fn($di) => [
                    'id'            => $di->id,
                    'uid'           => $di->uid,
                    'item_id'       => $di->item_id,
                    'status'        => $di->status,
                    'note'          => $di->note,
                    'operators'     => $di->operators ?? [],
                    'parts_data'    => $di->parts_data ?? [],
                    'is_finalized'  => $di->is_finalized,
                    'finalize_ts'   => $di->finalize_ts?->toISOString(),
                    'photos'        => $di->photos->map(fn($ph) => [
                        'uid' => $ph->uid, 'url' => $ph->url,
                        'context' => $ph->context, 'meta' => $ph->meta,
                    ]),
                ])->values(),
            ])->values();
        }

        return $base;
    }
}
