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

        // Only super_admin can access audit logs
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
        $query = Audit::with(['user'])->orderBy('created_at', 'desc');

        // Apply filters
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

        $html =
            '<button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#changesModal" onclick="showChanges(' .
            $audit->id .
            ')">
                    <i class="bi bi-eye"></i> View Changes
                </button>';

        return $html;
    }

    public function getChanges($id)
    {
        $audit = Audit::findOrFail($id);

        return response()->json([
            'old_values' => $audit->old_values,
            'new_values' => $audit->new_values,
            'event' => $audit->event,
            'model' => class_basename($audit->auditable_type),
            'created_at' => $audit->created_at->format('d M Y, H:i:s'),
        ]);
    }
}
