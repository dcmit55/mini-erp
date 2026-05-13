<?php

namespace App\Http\Controllers\Qc;

use App\Http\Controllers\Controller;
use App\Models\Qc\QcPackingItem;
use App\Models\Qc\QcProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QcPackingController extends Controller
{
    public function update(Request $request, QcProject $project, QcPackingItem $item): JsonResponse
    {
        $data = $request->validate([
            'is_checked' => 'boolean',
            'is_hidden'  => 'boolean',
        ]);

        $item->update($data);

        return response()->json($item);
    }

    public function addCustom(Request $request, QcProject $project): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $maxOrder = $project->packingItems()->max('sort_order') ?? 0;

        $item = QcPackingItem::create([
            'qc_project_id' => $project->id,
            'name'          => $data['name'],
            'type'          => 'custom',
            'sort_order'    => $maxOrder + 1,
        ]);

        return response()->json($item, 201);
    }

    public function destroy(QcProject $project, QcPackingItem $item): JsonResponse
    {
        // Hanya item custom yang bisa dihapus permanen; optional di-hidden
        if ($item->type === 'custom') {
            $item->delete();
        } else {
            $item->update(['is_hidden' => true, 'is_checked' => false]);
        }

        return response()->json(['message' => 'OK']);
    }

    public function verify(Request $request, QcProject $project): JsonResponse
    {
        $data = $request->validate([
            'verified' => 'required|boolean',
        ]);

        $project->update(['packing_verified' => $data['verified']]);

        return response()->json([
            'packing_verified' => $project->packing_verified,
            'progress'         => $project->fresh()->progress,
        ]);
    }
}
