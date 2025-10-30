<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;
use Illuminate\Support\Facades\Auth;
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
            ->rawColumns(['event_badge', 'changes'])
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
                'department_id' => ['model' => \App\Models\Department::class, 'field' => 'name'],
            ],
            'User' => [
                'department_id' => ['model' => \App\Models\Department::class, 'field' => 'name'],
            ],
            'PurchaseRequest' => [
                'inventory_id' => ['model' => \App\Models\Inventory::class, 'field' => 'name'],
                'project_id' => ['model' => \App\Models\Project::class, 'field' => 'name'],
                'supplier_id' => ['model' => \App\Models\Supplier::class, 'field' => 'name'],
                'currency_id' => ['model' => \App\Models\Currency::class, 'field' => 'name'],
                'requested_by' => ['model' => \App\Models\User::class, 'field' => 'username'],
            ],
            'MaterialPlanning' => [
                'project_id' => ['model' => \App\Models\Project::class, 'field' => 'name'],
                'unit_id' => ['model' => \App\Models\Unit::class, 'field' => 'name'],
                'requested_by' => ['model' => \App\Models\User::class, 'field' => 'username'],
            ],
            'Supplier' => [
                'location_id' => ['model' => \App\Models\LocationSupplier::class, 'field' => 'name'],
            ],
            'LeaveRequest' => [
                'employee_id' => ['model' => \App\Models\Employee::class, 'field' => 'name'],
            ],
            'ProjectPart' => [
                'project_id' => ['model' => \App\Models\Project::class, 'field' => 'name'],
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
}
