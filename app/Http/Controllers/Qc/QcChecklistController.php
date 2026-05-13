<?php

namespace App\Http\Controllers\Qc;

use App\Http\Controllers\Controller;
use App\Models\Qc\QcChecklistItem;
use App\Models\Qc\QcProject;
use App\Models\Qc\QcRejectLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QcChecklistController extends Controller
{
    public function update(Request $request, QcProject $project, int $itemId): JsonResponse
    {
        $data = $request->validate([
            'status'           => 'required|in:PASS,FAIL',
            'note'             => 'nullable|string|max:500',
            // Untuk FAIL: info defect
            'defect_category'  => 'required_if:status,FAIL|string',
            'severity'         => 'required_if:status,FAIL|in:Critical,Major',
            'fail_note'        => 'required_if:status,FAIL|string',
        ]);

        $item = QcChecklistItem::firstOrNew([
            'qc_project_id' => $project->id,
            'item_id'       => $itemId,
        ]);

        // Tentukan section_id dari item_id
        $item->section_id = $this->resolveSectionId($itemId);
        $item->status     = $data['status'];
        $item->note       = $data['note'] ?? null;
        $item->save();

        // Jika FAIL → buat reject log
        if ($data['status'] === 'FAIL') {
            QcRejectLog::create([
                'qc_project_id'    => $project->id,
                'source'           => 'finishing',
                'item_id'          => $itemId,
                'item_name'        => $request->input('item_name', 'Item #'.$itemId),
                'defect_category'  => $data['defect_category'],
                'severity'         => $data['severity'],
                'fail_note'        => $data['fail_note'],
                'fail_operator'    => $request->user()->name,
                'rework_status'    => 'OPEN',
                'rework_history'   => [[
                    'timestamp'  => now()->toISOString(),
                    'event'      => 'OPENED',
                    'old_status' => null,
                    'new_status' => 'OPEN',
                    'operator'   => $request->user()->name,
                    'note'       => $data['fail_note'],
                ]],
            ]);
        }

        $item->load('photos');

        return response()->json([
            'item'     => $item,
            'progress' => $project->fresh()->progress,
        ]);
    }

    private function resolveSectionId(int $itemId): int
    {
        return match(true) {
            $itemId >= 1  && $itemId <= 5  => 1,
            $itemId >= 6  && $itemId <= 8  => 2,
            $itemId >= 9  && $itemId <= 13 => 3,
            $itemId >= 14 && $itemId <= 16 => 4,
            $itemId >= 17 && $itemId <= 21 => 5,
            $itemId >= 22 && $itemId <= 24 => 6,
            $itemId >= 25 && $itemId <= 27 => 7,
            $itemId >= 28 && $itemId <= 31 => 8,
            $itemId >= 36 && $itemId <= 39 => 10,
            default                        => 1,
        };
    }
}
