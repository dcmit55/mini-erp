<?php

namespace App\Http\Controllers\Qc;

use App\Http\Controllers\Controller;
use App\Models\Qc\QcChecklistItem;
use App\Models\Qc\QcDailyItem;
use App\Models\Qc\QcPackingItem;
use App\Models\Qc\QcPhoto;
use App\Models\Qc\QcRejectLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QcPhotoController extends Controller
{
    private const ALLOWED_TYPES = [
        'checklist_item' => QcChecklistItem::class,
        'packing_item'   => QcPackingItem::class,
        'reject_log'     => QcRejectLog::class,
        'daily_item'     => QcDailyItem::class,
    ];

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'photoable_type' => 'required|in:checklist_item,packing_item,reject_log,daily_item',
            'photoable_uid'  => 'required|string',
            'context'        => 'nullable|string|max:50',
            'meta'           => 'nullable|string|max:255',
            'photo'          => 'required|image|max:5120', // max 5 MB
        ]);

        $modelClass = self::ALLOWED_TYPES[$data['photoable_type']];
        $owner      = $modelClass::where('uid', $data['photoable_uid'])->firstOrFail();

        $path = $request->file('photo')->store('qc/photos', 'public');

        $photo = QcPhoto::create([
            'uid'            => Str::uuid(),
            'photoable_type' => get_class($owner),
            'photoable_id'   => $owner->id,
            'path'           => $path,
            'context'        => $data['context'] ?? null,
            'meta'           => $data['meta'] ?? null,
        ]);

        return response()->json([
            'uid' => $photo->uid,
            'url' => $photo->url,
        ], 201);
    }

    public function destroy(QcPhoto $photo): JsonResponse
    {
        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
