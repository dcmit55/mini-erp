<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Logistic\Inventory;
use App\Models\Production\Project;
use App\Models\Logistic\MaterialUsage;
use App\Models\Logistic\GoodsOut;
use App\Models\Logistic\GoodsIn;
use App\Models\Logistic\MaterialRequest;
use App\Models\Finance\Currency;
use App\Models\Admin\User;
use App\Models\Hr\Employee;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TrashController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $rolesAllowed = ['super_admin'];
            if (!in_array(Auth::user()->role, $rolesAllowed)) {
                abort(403, 'Unauthorized');
            }
            return $next($request);
        });
    }

    public function index()
    {
        return view('admin.trash.index', [
            'inventories' => Inventory::onlyTrashed()->get(),
            'projects' => Project::onlyTrashed()->get(),
            'materialRequests' => MaterialRequest::onlyTrashed()
                ->with(['inventory', 'project'])
                ->get(),
            'goodsOuts' => GoodsOut::onlyTrashed()
                ->with(['inventory', 'project'])
                ->get(),
            'goodsIns' => GoodsIn::onlyTrashed()
                ->with(['inventory', 'project'])
                ->get(),
            'materialUsages' => MaterialUsage::onlyTrashed()
                ->with(['inventory', 'project'])
                ->get(),
            'currencies' => Currency::onlyTrashed()->get(),
            'users' => User::onlyTrashed()->get(),
            'employees' => Employee::onlyTrashed()->get(),
        ]);
    }

    public function restore(Request $request)
    {
        $model = $request->input('model');
        $id = $request->input('id');
        $modelClass = $this->getModelClass($model);
        if ($modelClass) {
            $item = $modelClass::onlyTrashed()->findOrFail($id);

            // Cek duplikasi nama sebelum restore
            if (($model === 'project' && Project::where('name', $item->name)->whereNull('deleted_at')->exists()) || ($model === 'inventory' && Inventory::where('name', $item->name)->whereNull('deleted_at')->exists()) || ($model === 'currency' && Currency::where('name', $item->name)->whereNull('deleted_at')->exists())) {
                return back()->with('error', ucfirst($model) . " <b>{$item->name}</b> cannot be restored because another active $model with the same name exists.");
            }

            $item->restore();

            $restoredInfo = $item->name ?? ($item->username ?? ($item->remark ?? (method_exists($item, 'getAttribute') ? $item->getAttribute('id') : $item->id)));

            return back()->with('success', ucfirst($model) . " <b>{$restoredInfo}</b> restored!");
        }
        return back()->with('error', 'Invalid model');
    }

    public function forceDelete(Request $request)
    {
        $model = $request->input('model');
        $id = $request->input('id');
        $modelClass = $this->getModelClass($model);

        if ($modelClass) {
            $item = $modelClass::onlyTrashed()->findOrFail($id);

            // Hapus file gambar
            if ($model === 'inventory') {
                // Hapus inventory image
                if ($item->img && Storage::disk('public')->exists($item->img)) {
                    Storage::disk('public')->delete($item->img);
                }
                // Hapus QR code file
                $qrCodePath = public_path('storage/qrcodes/' . $item->id . '.svg');
                if (file_exists($qrCodePath)) {
                    unlink($qrCodePath);
                }
            } elseif ($model === 'project') {
                // Hapus project image
                if ($item->img && Storage::disk('public')->exists($item->img)) {
                    Storage::disk('public')->delete($item->img);
                }
            }

            $deletedInfo = $item->name ?? ($item->username ?? ($item->remark ?? (method_exists($item, 'getAttribute') ? $item->getAttribute('id') : $item->id)));

            try {
                $item->forceDelete(); // Hapus permanen dari database
                return back()->with('success', ucfirst($model) . " <b>{$deletedInfo}</b> permanently deleted!");
            } catch (\Illuminate\Database\QueryException $e) {
                // Tangkap error constraint foreign key
                return back()->with('error', 'Cannot delete ' . ucfirst($model) . " <b>{$deletedInfo}</b> because this data is still used in another transaction.");
            }
        }

        return back()->with('error', 'Invalid model');
    }

    /**
     * Delete trash by date range
     */
    public function deleteByDateRange(Request $request)
    {
        try {
            $request->validate([
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from',
            ]);

            DB::beginTransaction();

            try {
                $models = [Inventory::class, Project::class, MaterialRequest::class, GoodsOut::class, GoodsIn::class, MaterialUsage::class, Currency::class, User::class, Employee::class];

                $deleteSummary = [];
                $totalDeleted = 0;

                foreach ($models as $modelClass) {
                    $modelName = class_basename($modelClass);

                    // Count record yang akan dihapus
                    $count = $modelClass
                        ::onlyTrashed()
                        ->whereBetween('deleted_at', [$request->date_from . ' 00:00:00', $request->date_to . ' 23:59:59'])
                        ->count();

                    if ($count > 0) {
                        // Delete permanent
                        $modelClass
                            ::onlyTrashed()
                            ->whereBetween('deleted_at', [$request->date_from . ' 00:00:00', $request->date_to . ' 23:59:59'])
                            ->forceDelete();

                        $deleteSummary[$modelName] = $count;
                        $totalDeleted += $count;

                        // Hapus file jika ada (untuk Inventory & Project)
                        if ($modelName === 'Inventory') {
                            $this->deleteInventoryFiles($modelClass, $request->date_from, $request->date_to);
                        } elseif ($modelName === 'Project') {
                            $this->deleteProjectFiles($modelClass, $request->date_from, $request->date_to);
                        }
                    }
                }

                if ($totalDeleted === 0) {
                    return response()->json(
                        [
                            'success' => false,
                            'message' => 'No trash records found in the specified date range.',
                        ],
                        404,
                    );
                }

                DB::commit();

                \Log::info('Delete trash by date range', [
                    'from' => $request->date_from,
                    'to' => $request->date_to,
                    'total_deleted' => $totalDeleted,
                    'summary' => $deleteSummary,
                    'deleted_by' => Auth::user()->username,
                ]);

                // Format summary message
                $summaryText = implode(', ', array_map(fn($model, $count) => "{$count} {$model}", array_keys($deleteSummary), $deleteSummary));

                return response()->json([
                    'success' => true,
                    'message' => "Successfully deleted {$totalDeleted} trash record(s) from {$request->date_from} to {$request->date_to}: {$summaryText}",
                    'deleted_count' => $totalDeleted,
                    'summary' => $deleteSummary,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'errors' => $e->errors(),
                ],
                422,
            );
        } catch (\Exception $e) {
            \Log::error('Error deleting trash by date range: ' . $e->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to delete trash: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Purge old trash (older than X days)
     */
    public function purgeOldTrash(Request $request)
    {
        try {
            $request->validate([
                'days' => 'required|integer|min:1|max:365',
            ]);

            $days = $request->days;
            $dateThreshold = now()->subDays($days);

            DB::beginTransaction();

            try {
                $models = [Inventory::class, Project::class, MaterialRequest::class, GoodsOut::class, GoodsIn::class, MaterialUsage::class, Currency::class, User::class, Employee::class];

                $deleteSummary = [];
                $totalDeleted = 0;

                foreach ($models as $modelClass) {
                    $modelName = class_basename($modelClass);

                    // Count record yang akan dihapus
                    $count = $modelClass::onlyTrashed()->where('deleted_at', '<', $dateThreshold)->count();

                    if ($count > 0) {
                        // Delete permanent
                        $modelClass::onlyTrashed()->where('deleted_at', '<', $dateThreshold)->forceDelete();

                        $deleteSummary[$modelName] = $count;
                        $totalDeleted += $count;

                        // Hapus file jika ada
                        if ($modelName === 'Inventory') {
                            $this->deleteInventoryFilesByThreshold($modelClass, $dateThreshold);
                        } elseif ($modelName === 'Project') {
                            $this->deleteProjectFilesByThreshold($modelClass, $dateThreshold);
                        }
                    }
                }

                DB::commit();

                \Log::info('Purge old trash', [
                    'days' => $days,
                    'before_date' => $dateThreshold,
                    'total_deleted' => $totalDeleted,
                    'summary' => $deleteSummary,
                    'purged_by' => Auth::user()->username,
                ]);

                // Format summary message
                $summaryText = implode(', ', array_map(fn($model, $count) => "{$count} {$model}", array_keys($deleteSummary), $deleteSummary));

                return response()->json([
                    'success' => true,
                    'message' => "Successfully purged {$totalDeleted} trash record(s) older than {$days} days: {$summaryText}",
                    'deleted_count' => $totalDeleted,
                    'summary' => $deleteSummary,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'errors' => $e->errors(),
                ],
                422,
            );
        } catch (\Exception $e) {
            \Log::error('Error purging old trash: ' . $e->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to purge trash: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Delete inventory files by date range
     */
    private function deleteInventoryFiles($modelClass, $dateFrom, $dateTo)
    {
        $inventories = $modelClass
            ::onlyTrashed()
            ->whereBetween('deleted_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->get(['id', 'img']);

        foreach ($inventories as $inventory) {
            if ($inventory->img && Storage::disk('public')->exists($inventory->img)) {
                Storage::disk('public')->delete($inventory->img);
            }
            $qrCodePath = public_path('storage/qrcodes/' . $inventory->id . '.svg');
            if (file_exists($qrCodePath)) {
                @unlink($qrCodePath);
            }
        }
    }

    /**
     * Delete project files by date range
     */
    private function deleteProjectFiles($modelClass, $dateFrom, $dateTo)
    {
        $projects = $modelClass
            ::onlyTrashed()
            ->whereBetween('deleted_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->get(['id', 'img']);

        foreach ($projects as $project) {
            if ($project->img && Storage::disk('public')->exists($project->img)) {
                Storage::disk('public')->delete($project->img);
            }
        }
    }

    /**
     * Delete inventory files by threshold
     */
    private function deleteInventoryFilesByThreshold($modelClass, $dateThreshold)
    {
        $inventories = $modelClass
            ::onlyTrashed()
            ->where('deleted_at', '<', $dateThreshold)
            ->get(['id', 'img']);

        foreach ($inventories as $inventory) {
            if ($inventory->img && Storage::disk('public')->exists($inventory->img)) {
                Storage::disk('public')->delete($inventory->img);
            }
            $qrCodePath = public_path('storage/qrcodes/' . $inventory->id . '.svg');
            if (file_exists($qrCodePath)) {
                @unlink($qrCodePath);
            }
        }
    }

    /**
     * Delete project files by threshold
     */
    private function deleteProjectFilesByThreshold($modelClass, $dateThreshold)
    {
        $projects = $modelClass
            ::onlyTrashed()
            ->where('deleted_at', '<', $dateThreshold)
            ->get(['id', 'img']);

        foreach ($projects as $project) {
            if ($project->img && Storage::disk('public')->exists($project->img)) {
                Storage::disk('public')->delete($project->img);
            }
        }
    }

    public function bulkAction(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        $modelMap = $request->input('model_map', []);
        $action = $request->input('action');

        if (!$ids || !$action) {
            return back()->with('error', 'No items selected or invalid action.');
        }

        $successInfo = [];
        $errorInfo = [];

        foreach ($ids as $id) {
            $model = $modelMap[$id] ?? null;
            $modelClass = $this->getModelClass($model);
            if ($modelClass) {
                $item = $modelClass::onlyTrashed()->find($id);
                if ($item) {
                    $info = $item->name ?? ($item->username ?? ($item->remark ?? (method_exists($item, 'getAttribute') ? $item->getAttribute('id') : $item->id)));

                    if ($action === 'restore') {
                        // Tambahkan pengecekan duplikasi nama
                        if (($model === 'project' && Project::where('name', $item->name)->whereNull('deleted_at')->exists()) || ($model === 'inventory' && Inventory::where('name', $item->name)->whereNull('deleted_at')->exists()) || ($model === 'currency' && Currency::where('name', $item->name)->whereNull('deleted_at')->exists())) {
                            $errorInfo[] = ucfirst($model) . " <b>{$info}</b> cannot be restored because another active $model with the same name exists.";
                            continue;
                        }
                        $item->restore();
                        $successInfo[] = ucfirst($model) . " <b>{$info}</b> restored!";
                    } elseif ($action === 'delete') {
                        try {
                            // Hapus files sebelum force delete
                            if ($model === 'inventory') {
                                if ($item->img && Storage::disk('public')->exists($item->img)) {
                                    Storage::disk('public')->delete($item->img);
                                }
                                $qrCodePath = public_path('storage/qrcodes/' . $item->id . '.svg');
                                if (file_exists($qrCodePath)) {
                                    @unlink($qrCodePath);
                                }
                            } elseif ($model === 'project') {
                                if ($item->img && Storage::disk('public')->exists($item->img)) {
                                    Storage::disk('public')->delete($item->img);
                                }
                            }

                            $item->forceDelete();
                            $successInfo[] = ucfirst($model) . " <b>{$info}</b>";
                        } catch (\Illuminate\Database\QueryException $e) {
                            $errorInfo[] = ucfirst($model) . " <b>{$info}</b>";
                        }
                    }
                }
            }
        }

        $messages = [];
        if ($successInfo) {
            $messages[] = ($action === 'restore' ? 'Restored: ' : 'Permanently deleted: ') . implode(', ', $successInfo) . ' successfully.';
        }
        if ($errorInfo) {
            $messages[] = 'Cannot delete because still used in another transaction: ' . implode(', ', $errorInfo) . '.';
        }

        // Gabungkan pesan dengan <br> agar baris berbeda
        $finalMessage = implode('<br>', $messages);

        if ($errorInfo) {
            return back()->with('error', $finalMessage);
        }
        return back()->with('success', $finalMessage);
    }

    private function getModelClass($model)
    {
        return match ($model) {
            'inventory' => Inventory::class,
            'project' => Project::class,
            'material_request' => MaterialRequest::class,
            'goods_out' => GoodsOut::class,
            'goods_in' => GoodsIn::class,
            'material_usage' => MaterialUsage::class,
            'currency' => Currency::class,
            'user' => User::class,
            'employee' => Employee::class,
            default => null,
        };
    }
}
