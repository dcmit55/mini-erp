<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AuditController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        // Audit hanya untuk super_admin
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->isSuperAdmin()) {
                abort(403, 'Access denied. Super admin only.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getDataTablesData($request);
        }

        return view('audit.index');
    }

    private function getDataTablesData(Request $request)
    {
        $query = Audit::with(['user'])->latest();

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->auditable_type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->addColumn('checkbox', function ($audit) {
                return '<input type="checkbox" class="select-audit" value="' . $audit->id . '">';
            })
            ->addColumn('user_name', function ($audit) {
                return $audit->user ? $audit->user->username : '(System)';
            })
            ->addColumn('model_name', function ($audit) {
                return class_basename($audit->auditable_type);
            })
            ->addColumn('event_badge', function ($audit) {
                $badges = [
                    'created' => 'badge bg-success',
                    'updated' => 'badge bg-warning',
                    'deleted' => 'badge bg-danger',
                    'restored' => 'badge bg-info',
                ];

                $class = $badges[$audit->event] ?? 'badge bg-secondary';
                return '<span class="' . $class . '">' . ucfirst($audit->event) . '</span>';
            })
            ->addColumn('changes', function ($audit) {
                return $this->formatChanges($audit);
            })
            ->addColumn('formatted_date', function ($audit) {
                return $audit->created_at->format('d M Y, H:i:s');
            })
            ->addColumn('actions', function ($audit) {
                return '<button type="button" class="btn btn-danger btn-sm delete-audit-btn" data-id="' .
                    $audit->id .
                    '" title="Delete">
                    <i class="bi bi-trash3"></i>
                </button>';
            })
            ->rawColumns(['checkbox', 'event_badge', 'changes', 'actions'])
            ->make(true);
    }

    private function formatChanges($audit)
    {
        if (empty($audit->old_values) && empty($audit->new_values)) {
            return '-';
        }

        return '<button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#changesModal" onclick="showChanges(' .
            $audit->id .
            ')">
                    <i class="bi bi-eye"></i> View Changes
                </button>';
    }

    public function getChanges($id)
    {
        $audit = Audit::findOrFail($id);

        // Format foreign keys dengan nama
        $oldValues = $audit->old_values;
        $newValues = $audit->new_values;

        $modelType = class_basename($audit->auditable_type);

        // Map foreign keys ke nama untuk berbagai model
        $this->formatForeignKeys($modelType, $oldValues, $newValues);

        return response()->json([
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'event' => $audit->event,
            'model' => $modelType,
            'created_at' => $audit->created_at->format('d M Y, H:i:s'),
        ]);
    }

    private function formatForeignKeys($modelType, &$oldValues, &$newValues)
    {
        $foreignKeyMappings = [
            'Employee' => [
                'department_id' => ['model' => \App\Models\Admin\Department::class, 'field' => 'name'],
            ],
            'User' => [
                'department_id' => ['model' => \App\Models\Admin\Department::class, 'field' => 'name'],
            ],
            'PurchaseRequest' => [
                'inventory_id' => ['model' => \App\Models\Logistic\Inventory::class, 'field' => 'name'],
                'project_id' => ['model' => \App\Models\Production\Project::class, 'field' => 'name'],
                'supplier_id' => ['model' => \App\Models\Procurement\Supplier::class, 'field' => 'name'],
                'currency_id' => ['model' => \App\Models\Finance\Currency::class, 'field' => 'name'],
                'requested_by' => ['model' => \App\Models\Admin\User::class, 'field' => 'username'],
            ],
            'MaterialPlanning' => [
                'project_id' => ['model' => \App\Models\Production\Project::class, 'field' => 'name'],
                'unit_id' => ['model' => \App\Models\Logistic\Unit::class, 'field' => 'name'],
                'requested_by' => ['model' => \App\Models\Admin\User::class, 'field' => 'username'],
            ],
            'Supplier' => [
                'location_id' => ['model' => \App\Models\Procurement\LocationSupplier::class, 'field' => 'name'],
            ],
            'LeaveRequest' => [
                'employee_id' => ['model' => \App\Models\Hr\Employee::class, 'field' => 'name'],
            ],
            'ProjectPart' => [
                'project_id' => ['model' => \App\Models\Production\Project::class, 'field' => 'name'],
            ],
        ];

        if (!isset($foreignKeyMappings[$modelType])) {
            return;
        }

        foreach ($foreignKeyMappings[$modelType] as $fieldName => $mapping) {
            $this->replaceForeignKeyValue($oldValues, $fieldName, $mapping);
            $this->replaceForeignKeyValue($newValues, $fieldName, $mapping);
        }
    }

    private function replaceForeignKeyValue(&$values, $fieldName, $mapping)
    {
        if (!isset($values[$fieldName])) {
            return;
        }

        $id = $values[$fieldName];
        if (empty($id)) {
            return;
        }

        try {
            $modelClass = $mapping['model'];
            $fieldToGet = $mapping['field'];

            $record = $modelClass::find($id);
            if ($record) {
                $values[$fieldName] = "{$record->{$fieldToGet}} (ID: {$id})";
            } else {
                $values[$fieldName] = "(ID: {$id}) - [Deleted]";
            }
        } catch (\Exception $e) {
            // Jika error, biarkan nilai asli
        }
    }

    /**
     * Delete single audit record
     */
    public function destroy($id)
    {
        try {
            $audit = Audit::findOrFail($id);
            $modelName = class_basename($audit->auditable_type);
            $eventName = ucfirst($audit->event);

            $audit->delete();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Audit log for <b>{$modelName}</b> ({$eventName}) deleted successfully!",
                ]);
            }

            return redirect()->route('audit.index')->with('success', 'Audit log deleted successfully!');
        } catch (\Exception $e) {
            \Log::error('Error deleting audit log: ' . $e->getMessage());

            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to delete audit log: ' . $e->getMessage()], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Failed to delete audit log: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete audit records
     */
    public function bulkDelete(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json(['success' => false, 'message' => 'No audit records selected.'], 422);
            }

            // Validasi semua ID ada di database
            $validIds = Audit::whereIn('id', $ids)->pluck('id')->toArray();

            if (count($validIds) === 0) {
                return response()->json(['success' => false, 'message' => 'No valid audit records found.'], 404);
            }

            // Gunakan transaction untuk delete multiple records
            DB::beginTransaction();

            try {
                // Get details sebelum delete untuk logging
                $auditsToDelete = Audit::whereIn('id', $validIds)->get();

                // Hitung per model dan event
                $deleteSummary = [];
                foreach ($auditsToDelete as $audit) {
                    $modelName = class_basename($audit->auditable_type);
                    $key = "{$modelName} ({$audit->event})";
                    $deleteSummary[$key] = ($deleteSummary[$key] ?? 0) + 1;
                }

                // Delete records
                $deletedCount = Audit::whereIn('id', $validIds)->delete();

                DB::commit();

                \Log::info('Bulk delete audit logs', [
                    'count' => $deletedCount,
                    'summary' => $deleteSummary,
                    'deleted_by' => Auth::user()->username,
                ]);

                // Format message
                $summaryText = implode(', ', array_map(fn($k, $v) => "{$v} {$k}", array_keys($deleteSummary), $deleteSummary));

                return response()->json([
                    'success' => true,
                    'message' => "Successfully deleted {$deletedCount} audit log(s): {$summaryText}",
                    'deleted_count' => $deletedCount,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            \Log::error('Error in bulk delete audit logs: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to delete audit logs: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete audit logs by date range
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
                $auditsBefore = Audit::whereBetween('created_at', [$request->date_from . ' 00:00:00', $request->date_to . ' 23:59:59'])->count();

                if ($auditsBefore === 0) {
                    return response()->json(['success' => false, 'message' => 'No audit records found in the specified date range.'], 404);
                }

                $deletedCount = Audit::whereBetween('created_at', [$request->date_from . ' 00:00:00', $request->date_to . ' 23:59:59'])->delete();

                DB::commit();

                \Log::info('Delete audit logs by date range', [
                    'from' => $request->date_from,
                    'to' => $request->date_to,
                    'deleted_count' => $deletedCount,
                    'deleted_by' => Auth::user()->username,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Successfully deleted {$deletedCount} audit log(s) from {$request->date_from} to {$request->date_to}",
                    'deleted_count' => $deletedCount,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Error deleting audit logs by date range: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to delete audit logs: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Purge old audit logs (older than X days)
     */
    public function purgeOldLogs(Request $request)
    {
        try {
            $request->validate([
                'days' => 'required|integer|min:1|max:365',
            ]);

            $days = $request->days;
            $dateThreshold = now()->subDays($days);

            DB::beginTransaction();

            try {
                $deletedCount = Audit::where('created_at', '<', $dateThreshold)->delete();

                DB::commit();

                \Log::info('Purge old audit logs', [
                    'days' => $days,
                    'before_date' => $dateThreshold,
                    'deleted_count' => $deletedCount,
                    'purged_by' => Auth::user()->username,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Successfully purged {$deletedCount} audit log(s) older than {$days} days",
                    'deleted_count' => $deletedCount,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Error purging old audit logs: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to purge audit logs: ' . $e->getMessage()], 500);
        }
    }
}
