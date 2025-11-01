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
}
