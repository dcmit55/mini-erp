<?php

namespace App\Http\Controllers\Qc;

use App\Http\Controllers\Controller;
use App\Models\Qc\QcDailyItem;
use App\Models\Qc\QcDailyProgress;
use App\Models\Qc\QcProject;
use App\Models\Qc\QcRejectLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QcDailyProgressController extends Controller
{
    public function show(QcProject $project, string $date): JsonResponse
    {
        $dp = $project->dailyProgress()
            ->with('items.photos')
            ->where('date', $date)
            ->first();

        if (!$dp) {
            return response()->json(null);
        }

        return response()->json($this->formatProgress($dp));
    }

    public function upsert(Request $request, QcProject $project, string $date): JsonResponse
    {
        $data = $request->validate([
            'session_note' => 'nullable|string|max:1000',
            'operators'    => 'nullable|array',
            'operators.*'  => 'string|max:100',
        ]);

        $dp = QcDailyProgress::firstOrCreate(
            ['qc_project_id' => $project->id, 'date' => $date],
            ['operators' => [], 'session_note' => null]
        );

        $dp->update([
            'session_note' => $data['session_note'] ?? $dp->session_note,
            'operators'    => $data['operators'] ?? $dp->operators,
        ]);

        $dp->load('items.photos');

        return response()->json($this->formatProgress($dp));
    }

    public function updateItem(Request $request, QcProject $project, string $date, string $itemId): JsonResponse
    {
        $data = $request->validate([
            'status'     => 'nullable|in:PASS,FAIL',
            'note'       => 'nullable|string|max:500',
            'operators'  => 'nullable|array',
            'operators.*'=> 'string|max:100',
            'parts_data' => 'nullable|array',
        ]);

        $dp = QcDailyProgress::firstOrCreate(
            ['qc_project_id' => $project->id, 'date' => $date],
            ['operators' => [], 'session_note' => null]
        );

        $item = QcDailyItem::firstOrNew([
            'qc_daily_progress_id' => $dp->id,
            'item_id'              => $itemId,
        ]);

        if (isset($data['status']))     $item->status     = $data['status'];
        if (array_key_exists('note', $data))        $item->note       = $data['note'];
        if (isset($data['operators']))  $item->operators  = $data['operators'];
        if (isset($data['parts_data'])) $item->parts_data = $data['parts_data'];

        $item->save();
        $item->load('photos');

        return response()->json([
            'item'     => $this->formatItem($item),
            'progress' => $project->fresh()->progress,
        ]);
    }

    public function finalizeItem(Request $request, QcProject $project, string $date, string $itemId): JsonResponse
    {
        $data = $request->validate([
            'finalized'            => 'required|boolean',
            // Reject log fields jika ada defect saat finalisasi
            'defect_category'      => 'nullable|string|max:100',
            'severity'             => 'nullable|in:Critical,Major',
            'fail_note'            => 'nullable|string',
            'fail_operator'        => 'nullable|string|max:100',
            'rework_assigned_to'   => 'nullable|string|max:100',
            'target_completion_date' => 'nullable|date',
        ]);

        $dp = QcDailyProgress::where('qc_project_id', $project->id)
            ->where('date', $date)
            ->firstOrFail();

        $item = QcDailyItem::where('qc_daily_progress_id', $dp->id)
            ->where('item_id', $itemId)
            ->firstOrFail();

        $item->is_finalized = $data['finalized'];
        $item->finalize_ts  = $data['finalized'] ? now() : null;
        $item->save();

        // Buat reject log jika ada defect
        if ($data['finalized'] && !empty($data['defect_category'])) {
            QcRejectLog::create([
                'qc_project_id'          => $project->id,
                'source'                 => 'daily_progress',
                'daily_item_id'          => $itemId,
                'fail_date_str'          => $date,
                'item_name'              => $request->input('item_name', 'Item '.$itemId),
                'defect_category'        => $data['defect_category'],
                'severity'               => $data['severity'] ?? 'Major',
                'fail_note'              => $data['fail_note'] ?? '',
                'fail_operator'          => $data['fail_operator'] ?? $request->user()->name,
                'rework_assigned_to'     => $data['rework_assigned_to'] ?? null,
                'target_completion_date' => $data['target_completion_date'] ?? null,
                'rework_status'          => 'OPEN',
                'rework_history'         => [[
                    'timestamp'  => now()->toISOString(),
                    'event'      => 'OPENED',
                    'old_status' => null,
                    'new_status' => 'OPEN',
                    'operator'   => $data['fail_operator'] ?? $request->user()->name,
                    'note'       => $data['fail_note'] ?? '',
                ]],
            ]);
        }

        $item->load('photos');

        return response()->json([
            'item'     => $this->formatItem($item),
            'progress' => $project->fresh()->progress,
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────

    private function formatProgress(QcDailyProgress $dp): array
    {
        return [
            'id'           => $dp->id,
            'uid'          => $dp->uid,
            'date'         => $dp->date->toDateString(),
            'session_note' => $dp->session_note,
            'operators'    => $dp->operators ?? [],
            'items'        => $dp->items->map(fn($i) => $this->formatItem($i))->values(),
        ];
    }

    private function formatItem(QcDailyItem $i): array
    {
        return [
            'id'           => $i->id,
            'uid'          => $i->uid,
            'item_id'      => $i->item_id,
            'status'       => $i->status,
            'note'         => $i->note,
            'operators'    => $i->operators ?? [],
            'parts_data'   => $i->parts_data ?? [],
            'is_finalized' => $i->is_finalized,
            'finalize_ts'  => $i->finalize_ts?->toISOString(),
            'photos'       => $i->relationLoaded('photos')
                ? $i->photos->map(fn($ph) => [
                    'uid'     => $ph->uid,
                    'url'     => $ph->url,
                    'context' => $ph->context,
                    'meta'    => $ph->meta,
                ])->values()
                : [],
        ];
    }
}
