<?php

namespace App\Http\Controllers\Qc;

use App\Http\Controllers\Controller;
use App\Models\Qc\QcDailyItem;
use App\Models\Qc\QcDailyProgress;
use App\Models\Qc\QcProject;
use App\Models\Qc\QcRejectLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QcStageProductionController extends Controller
{
    // GET /projects/{uid}/stages/{stage}/records
    public function records(QcProject $project, string $stage): JsonResponse
    {
        $items = QcDailyItem::whereHas('dailyProgress', fn($q) =>
            $q->where('qc_project_id', $project->id)->where('stage', $stage)
        )
        ->with(['photos', 'dailyProgress'])
        ->latest()
        ->get();

        return response()->json($items->map(fn($i) => $this->formatRecord($i))->values());
    }

    // POST /projects/{uid}/stages/{stage}/records
    public function store(Request $request, QcProject $project, string $stage): JsonResponse
    {
        $data = $request->validate([
            'date'     => 'required|date',
            'operator' => 'required|string|max:100',
            'part'     => 'required|string|max:100',
            'qty'      => 'required|integer|min:1',
            'notes'    => 'nullable|string|max:500',
        ]);

        $dp = QcDailyProgress::firstOrCreate(
            ['qc_project_id' => $project->id, 'stage' => $stage, 'date' => $data['date']],
            ['operators' => [], 'session_note' => null]
        );

        $item = QcDailyItem::create([
            'qc_daily_progress_id' => $dp->id,
            'item_id'              => (string) Str::uuid(),
            'operators'            => [$data['operator']],
            'parts_data'           => [
                'part'         => $data['part'],
                'qty_produced' => (int) $data['qty'],
                'qty_pass'     => null,
                'qty_fail'     => null,
            ],
            'note'   => $data['notes'] ?? null,
            'status' => null,
        ]);

        $item->load(['photos', 'dailyProgress']);

        return response()->json($this->formatRecord($item), 201);
    }

    // PUT /projects/{uid}/stages/{stage}/records/{itemUid}
    public function update(Request $request, QcProject $project, string $stage, string $itemUid): JsonResponse
    {
        $item = $this->findItem($project, $stage, $itemUid);

        $data = $request->validate([
            'operator' => 'nullable|string|max:100',
            'part'     => 'nullable|string|max:100',
            'qty'      => 'nullable|integer|min:1',
            'notes'    => 'nullable|string|max:500',
        ]);

        $partsData = $item->parts_data ?? [];
        if (isset($data['part']))     $partsData['part']         = $data['part'];
        if (isset($data['qty']))      $partsData['qty_produced']  = (int) $data['qty'];

        $item->update([
            'operators'  => isset($data['operator']) ? [$data['operator']] : $item->operators,
            'parts_data' => $partsData,
            'note'       => $data['notes'] ?? $item->note,
        ]);

        return response()->json($this->formatRecord($item->fresh(['photos', 'dailyProgress'])));
    }

    // POST /projects/{uid}/stages/{stage}/records/{itemUid}/inspect
    public function inspect(Request $request, QcProject $project, string $stage, string $itemUid): JsonResponse
    {
        $item = $this->findItem($project, $stage, $itemUid);

        $data = $request->validate([
            'qty_pass'          => 'required|integer|min:0',
            'qty_fail'          => 'required|integer|min:0',
            'defect_category'   => 'nullable|string|max:100',
            'defect_desc'       => 'nullable|string|max:500',
            'severity'          => 'nullable|in:Critical,Major,Minor',
            'corrective_action' => 'nullable|string|max:1000',
        ]);

        $partsData                       = $item->parts_data ?? [];
        $partsData['qty_pass']           = $data['qty_pass'];
        $partsData['qty_fail']           = $data['qty_fail'];
        $partsData['defect_cat']         = $data['defect_category'] ?? null;
        $partsData['defect_desc']        = $data['defect_desc'] ?? null;
        $partsData['severity']           = $data['severity'] ?? null;
        $partsData['corrective_action']  = $data['corrective_action'] ?? null;

        $item->update([
            'status'       => $data['qty_fail'] > 0 ? 'FAIL' : 'PASS',
            'parts_data'   => $partsData,
            'is_finalized' => true,
            'finalize_ts'  => now(),
        ]);

        if ($data['qty_fail'] > 0) {
            $count    = QcRejectLog::where('qc_project_id', $project->id)->count() + 1;
            $rejectId = 'REJ-' . str_pad($count, 3, '0', STR_PAD_LEFT);

            QcRejectLog::create([
                'reject_id'              => $rejectId,
                'qc_project_id'          => $project->id,
                'source'                 => 'daily_progress',
                'stage'                  => $stage,
                'daily_item_id'          => $item->item_id,
                'fail_date_str'          => $item->dailyProgress->date,
                'item_name'              => $partsData['part'] ?? 'Item',
                'defect_category'        => $data['defect_category'] ?? 'Other',
                'severity'               => $data['severity'] ?? 'Major',
                'fail_note'              => $data['defect_desc'] ?? '',
                'corrective_action'      => $data['corrective_action'] ?? null,
                'qty_reject'             => $data['qty_fail'],
                'fail_operator'          => $item->operators[0] ?? $request->user()->name,
                'rework_status'          => 'OPEN',
                'rework_history'         => [[
                    'timestamp'  => now()->toISOString(),
                    'event'      => 'OPENED',
                    'old_status' => null,
                    'new_status' => 'OPEN',
                    'note'       => $data['defect_desc'] ?? '',
                ]],
            ]);
        }

        return response()->json($this->formatRecord($item->fresh(['photos', 'dailyProgress'])));
    }

    // GET /projects/{uid}/stages/{stage}/reject-logs
    public function rejectLogs(QcProject $project, string $stage): JsonResponse
    {
        $logs = QcRejectLog::where('qc_project_id', $project->id)
            ->where('stage', $stage)
            ->with('photos')
            ->latest()
            ->get();

        return response()->json($logs->map(fn($l) => $this->formatLog($l))->values());
    }

    // POST /projects/{uid}/stages/{stage}/reject-logs
    public function storeRejectLog(Request $request, QcProject $project, string $stage): JsonResponse
    {
        $data = $request->validate([
            'item_name'              => 'required|string|max:200',
            'defect_category'        => 'nullable|string|max:100',
            'fail_note'              => 'nullable|string|max:1000',
            'severity'               => 'nullable|in:Critical,Major,Minor',
            'qty_reject'             => 'nullable|integer|min:0',
            'root_cause'             => 'nullable|string|max:1000',
            'corrective_action'      => 'nullable|string|max:1000',
            'rework_assigned_to'     => 'nullable|string|max:100',
            'target_completion_date' => 'nullable|date',
            'rework_status'          => 'nullable|in:OPEN,IN_REPAIR,REPAIRED-PQC,CLOSED',
        ]);

        $count    = QcRejectLog::where('qc_project_id', $project->id)->count() + 1;
        $rejectId = 'REJ-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        $log = QcRejectLog::create(array_merge($data, [
            'reject_id'      => $rejectId,
            'qc_project_id'  => $project->id,
            'source'         => 'finishing',
            'stage'          => $stage,
            'defect_category'=> $data['defect_category'] ?? 'Other',
            'fail_note'      => $data['fail_note'] ?? '',
            'severity'       => $data['severity'] ?? 'Major',
            'rework_status'  => $data['rework_status'] ?? 'OPEN',
            'rework_history' => [[
                'timestamp'  => now()->toISOString(),
                'event'      => 'OPENED',
                'old_status' => null,
                'new_status' => $data['rework_status'] ?? 'OPEN',
                'note'       => 'Created via finishing spreadsheet',
            ]],
        ]));

        return response()->json($this->formatLog($log->fresh('photos')), 201);
    }

    // POST /projects/{uid}/stages/{stage}/reject-logs/batch
    public function batchStoreRejectLogs(Request $request, QcProject $project, string $stage): JsonResponse
    {
        $validated = $request->validate([
            'rows'                          => 'required|array|min:1|max:500',
            'rows.*.item_name'              => 'required|string|max:200',
            'rows.*.defect_category'        => 'nullable|string|max:100',
            'rows.*.fail_note'              => 'nullable|string|max:1000',
            'rows.*.severity'              => 'nullable|in:Critical,Major',
            'rows.*.qty_reject'            => 'nullable|integer|min:0',
            'rows.*.root_cause'            => 'nullable|string|max:1000',
            'rows.*.corrective_action'     => 'nullable|string|max:1000',
            'rows.*.rework_assigned_to'    => 'nullable|string|max:100',
            'rows.*.target_completion_date'=> 'nullable|date',
            'rows.*.rework_status'         => 'nullable|in:OPEN,IN_REPAIR,REPAIRED-PQC,CLOSED',
        ]);

        $baseCount = QcRejectLog::where('qc_project_id', $project->id)->count();
        $created   = [];

        foreach ($validated['rows'] as $i => $row) {
            $rejectId = 'REJ-' . str_pad($baseCount + $i + 1, 3, '0', STR_PAD_LEFT);
            $log = QcRejectLog::create([
                'reject_id'              => $rejectId,
                'qc_project_id'          => $project->id,
                'source'                 => 'finishing',
                'stage'                  => $stage,
                'item_name'              => $row['item_name'],
                'defect_category'        => $row['defect_category'] ?? 'Other',
                'fail_note'              => $row['fail_note'] ?? '',
                'severity'               => $row['severity'] ?? 'Major',
                'qty_reject'             => $row['qty_reject'] ?? null,
                'root_cause'             => $row['root_cause'] ?? null,
                'corrective_action'      => $row['corrective_action'] ?? null,
                'rework_assigned_to'     => $row['rework_assigned_to'] ?? null,
                'target_completion_date' => $row['target_completion_date'] ?? null,
                'rework_status'          => $row['rework_status'] ?? 'OPEN',
                'rework_history'         => [[
                    'timestamp'  => now()->toISOString(),
                    'event'      => 'OPENED',
                    'old_status' => null,
                    'new_status' => $row['rework_status'] ?? 'OPEN',
                    'note'       => 'Imported via Excel/CSV',
                ]],
            ]);
            $created[] = $this->formatLog($log);
        }

        return response()->json(['created' => count($created), 'logs' => $created], 201);
    }

    // PUT /projects/{uid}/stages/{stage}/reject-logs/{logUid}
    public function updateRejectLog(Request $request, QcProject $project, string $stage, string $logUid): JsonResponse
    {
        $log  = QcRejectLog::where('uid', $logUid)->where('qc_project_id', $project->id)->firstOrFail();
        $data = $request->validate([
            'item_name'              => 'nullable|string|max:200',
            'defect_category'        => 'nullable|string|max:100',
            'fail_note'              => 'nullable|string|max:1000',
            'severity'               => 'nullable|in:Critical,Major,Minor',
            'qty_reject'             => 'nullable|integer|min:0',
            'root_cause'             => 'nullable|string|max:1000',
            'corrective_action'      => 'nullable|string|max:1000',
            'rework_assigned_to'     => 'nullable|string|max:100',
            'target_completion_date' => 'nullable|date',
            'rework_status'          => 'nullable|in:OPEN,IN_REPAIR,REPAIRED-PQC,CLOSED',
            'note'                   => 'nullable|string|max:300',
        ]);

        $oldStatus = $log->rework_status;

        if (isset($data['rework_status']) && $data['rework_status'] !== $oldStatus) {
            $log->appendHistory([
                'event'      => 'STATUS_CHANGE',
                'old_status' => $oldStatus,
                'new_status' => $data['rework_status'],
                'operator'   => $request->user()->name,
                'note'       => $data['note'] ?? '',
            ]);
            if ($data['rework_status'] === 'CLOSED') {
                $log->closed_date = now();
            }
        }

        $log->fill(array_filter($data, fn($v, $k) => $k !== 'note' && $v !== null, ARRAY_FILTER_USE_BOTH));
        $log->save();

        return response()->json($this->formatLog($log->fresh('photos')));
    }

    // GET /projects/{uid}/stages/{stage}/gallery
    public function gallery(QcProject $project, string $stage): JsonResponse
    {
        $itemUids = QcDailyItem::whereHas('dailyProgress', fn($q) =>
            $q->where('qc_project_id', $project->id)->where('stage', $stage)
        )->pluck('uid');

        $logUids = QcRejectLog::where('qc_project_id', $project->id)
            ->where('stage', $stage)
            ->pluck('uid');

        $photos = \App\Models\Qc\QcPhoto::where(function ($q) use ($itemUids, $logUids) {
            $q->where(fn($q2) => $q2->where('photoable_type', 'daily_item')->whereIn('photoable_uid', $itemUids))
              ->orWhere(fn($q2) => $q2->where('photoable_type', 'reject_log')->whereIn('photoable_uid', $logUids));
        })->latest()->get();

        return response()->json($photos->map(fn($p) => [
            'uid'     => $p->uid,
            'url'     => $p->url,
            'context' => $p->context,
            'meta'    => $p->meta,
            'created_at' => $p->created_at->toISOString(),
        ])->values());
    }

    // GET /projects/{uid}/stages/{stage}/history
    public function history(QcProject $project, string $stage): JsonResponse
    {
        $records = QcDailyItem::whereHas('dailyProgress', fn($q) =>
            $q->where('qc_project_id', $project->id)->where('stage', $stage)
        )->with('dailyProgress')->latest()->get();

        $logs = QcRejectLog::where('qc_project_id', $project->id)
            ->where('stage', $stage)->latest()->get();

        $events = collect();

        foreach ($records as $r) {
            $pd = $r->parts_data ?? [];
            $events->push([
                'ts'     => $r->created_at->toISOString(),
                'type'   => 'production',
                'icon'   => 'activity',
                'title'  => 'Production record added',
                'detail' => ($pd['part'] ?? '?') . ' — Qty: ' . ($pd['qty_produced'] ?? 0),
                'user'   => $r->operators[0] ?? '—',
            ]);
            if ($r->is_finalized) {
                $events->push([
                    'ts'     => $r->finalize_ts?->toISOString() ?? $r->updated_at->toISOString(),
                    'type'   => $r->status === 'FAIL' ? 'fail' : 'pass',
                    'icon'   => $r->status === 'FAIL' ? 'x-circle' : 'check-circle',
                    'title'  => 'Inspection ' . ($r->status === 'FAIL' ? 'failed' : 'passed'),
                    'detail' => 'Pass: ' . ($pd['qty_pass'] ?? 0) . ' / Fail: ' . ($pd['qty_fail'] ?? 0),
                    'user'   => $r->operators[0] ?? '—',
                ]);
            }
        }

        foreach ($logs as $l) {
            foreach ($l->rework_history ?? [] as $h) {
                $events->push([
                    'ts'     => $h['timestamp'],
                    'type'   => 'rework',
                    'icon'   => 'wrench',
                    'title'  => ucfirst(str_replace('_', ' ', strtolower($h['event'] ?? 'update'))),
                    'detail' => ($l->reject_id ?? $l->uid) . ' — ' . ($h['new_status'] ?? ''),
                    'user'   => $h['operator'] ?? '—',
                ]);
            }
        }

        return response()->json($events->sortByDesc('ts')->values());
    }

    // ── Helpers ────────────────────────────────────────────────────────

    private function findItem(QcProject $project, string $stage, string $itemUid): QcDailyItem
    {
        return QcDailyItem::where('uid', $itemUid)
            ->whereHas('dailyProgress', fn($q) =>
                $q->where('qc_project_id', $project->id)->where('stage', $stage)
            )->with(['dailyProgress', 'photos'])
            ->firstOrFail();
    }

    private function formatRecord(QcDailyItem $i): array
    {
        $pd = $i->parts_data ?? [];
        return [
            'uid'          => $i->uid,
            'date'         => $i->dailyProgress?->date?->toDateString(),
            'operator'     => $i->operators[0] ?? '—',
            'part'         => $pd['part'] ?? '—',
            'qty_produced' => $pd['qty_produced'] ?? 0,
            'qty_pass'     => $pd['qty_pass'] ?? null,
            'qty_fail'     => $pd['qty_fail'] ?? null,
            'defect_cat'   => $pd['defect_cat'] ?? null,
            'defect_desc'       => $pd['defect_desc'] ?? null,
            'severity'          => $pd['severity'] ?? null,
            'corrective_action' => $pd['corrective_action'] ?? null,
            'notes'             => $i->note,
            'status'       => $i->status,         // null | PASS | FAIL
            'is_finalized' => $i->is_finalized,
            'finalize_ts'  => $i->finalize_ts?->toISOString(),
            'photos'       => $i->relationLoaded('photos') ? $i->photos->map(fn($p) => [
                'uid' => $p->uid, 'url' => $p->url, 'context' => $p->context,
            ])->values() : [],
        ];
    }

    private function formatLog(QcRejectLog $l): array
    {
        return [
            'uid'                    => $l->uid,
            'reject_id'              => $l->reject_id ?? ('REJ-' . str_pad($l->id, 3, '0', STR_PAD_LEFT)),
            'stage'                  => $l->stage,
            'item_name'              => $l->item_name,
            'defect_category'        => $l->defect_category,
            'severity'               => $l->severity,
            'fail_note'              => $l->fail_note,
            'qty_reject'             => $l->qty_reject,
            'fail_operator'          => $l->fail_operator,
            'fail_date'              => $l->fail_date_str?->toDateString(),
            'root_cause'             => $l->root_cause,
            'corrective_action'      => $l->corrective_action,
            'rework_assigned_to'     => $l->rework_assigned_to,
            'target_completion_date' => $l->target_completion_date?->toDateString(),
            'rework_status'          => $l->rework_status,
            'closed_date'            => $l->closed_date?->toISOString(),
            'rework_history'         => $l->rework_history ?? [],
            'photos'                 => $l->relationLoaded('photos') ? $l->photos->map(fn($p) => [
                'uid' => $p->uid, 'url' => $p->url,
            ])->values() : [],
        ];
    }
}
