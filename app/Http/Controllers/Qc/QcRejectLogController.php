<?php

namespace App\Http\Controllers\Qc;

use App\Http\Controllers\Controller;
use App\Models\Qc\QcProject;
use App\Models\Qc\QcRejectLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QcRejectLogController extends Controller
{
    public function index(QcProject $project): JsonResponse
    {
        $logs = $project->rejectLogs()->with('photos')->orderByDesc('created_at')->get();

        return response()->json($logs);
    }

    public function store(Request $request, QcProject $project): JsonResponse
    {
        $data = $request->validate([
            'source'                  => 'required|in:finishing,daily_progress',
            'item_id'                 => 'nullable|integer',
            'daily_item_id'           => 'nullable|string|max:10',
            'fail_date_str'           => 'nullable|date',
            'item_name'               => 'required|string|max:255',
            'defect_category'         => 'required|string|max:100',
            'severity'                => 'required|in:Critical,Major',
            'fail_note'               => 'required|string',
            'fail_operator'           => 'nullable|string|max:100',
            'rework_assigned_to'      => 'nullable|string|max:100',
            'target_completion_date'  => 'nullable|date',
        ]);

        $log = QcRejectLog::create(array_merge($data, [
            'qc_project_id' => $project->id,
            'rework_status' => 'OPEN',
            'rework_history' => [[
                'timestamp'  => now()->toISOString(),
                'event'      => 'OPENED',
                'old_status' => null,
                'new_status' => 'OPEN',
                'operator'   => $data['fail_operator'] ?? $request->user()->name,
                'note'       => $data['fail_note'],
            ]],
        ]));

        return response()->json($log, 201);
    }

    public function update(Request $request, QcRejectLog $log): JsonResponse
    {
        $data = $request->validate([
            'rework_status'          => 'required|in:OPEN,IN_REPAIR,REPAIRED-PQC,CLOSED',
            'operator'               => 'required|string|max:100',
            'note'                   => 'required|string',
            'rework_assigned_to'     => 'nullable|string|max:100',
            'target_completion_date' => 'nullable|date',
        ]);

        $oldStatus = $log->rework_status;
        $newStatus = $data['rework_status'];

        $log->appendHistory([
            'event'      => $newStatus === 'CLOSED' ? 'CLOSED' : ($newStatus === 'REPAIRED-PQC' ? 'REPAIRED-PQC' : 'UPDATED'),
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'operator'   => $data['operator'],
            'note'       => $data['note'],
        ]);

        $log->rework_status = $newStatus;

        if ($newStatus === 'CLOSED') {
            $log->closed_date = now();
        }

        if (!empty($data['rework_assigned_to'])) {
            $log->rework_assigned_to = $data['rework_assigned_to'];
        }

        if (!empty($data['target_completion_date'])) {
            $log->target_completion_date = $data['target_completion_date'];
        }

        $log->save();

        return response()->json($log);
    }
}
