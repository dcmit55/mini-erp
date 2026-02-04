<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Production\JobOrder;
use App\Models\Production\Project;
use App\Models\Admin\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use App\Services\Lark\LarkJobOrderSyncService;

class JobOrderController extends Controller
{
    // INDEX - Tampilkan semua job order dengan filter
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = JobOrder::with(['project:id,name', 'department:id,name', 'creator:id,username'])->latest();

            // Apply filters
            if ($request->filled('project')) {
                $query->where('project_id', $request->project);
            }

            if ($request->filled('department')) {
                $query->where('department_id', $request->department);
            }

            if ($request->filled('custom_search')) {
                $search = $request->custom_search;
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            }

            return DataTables::of($query)
                ->addColumn('job_order_id', function ($jo) {
                    return $jo->id;
                })
                ->addColumn('project_name', function ($jo) {
                    return $jo->project ? $jo->project->name : '-';
                })
                ->addColumn('department_name', function ($jo) {
                    return $jo->department ? $jo->department->name : '-';
                })
                ->addColumn('start_date', function ($jo) {
                    return $jo->start_date ? $jo->start_date->format('Y-m-d') : '-';
                })
                ->addColumn('end_date', function ($jo) {
                    return $jo->end_date ? $jo->end_date->format('Y-m-d') : '-';
                })
                ->addColumn('description', function ($jo) {
                    if ($jo->description) {
                        return '<span data-bs-toggle="tooltip" title="' . htmlspecialchars($jo->description) . '">' . \Str::limit($jo->description, 50) . '</span>';
                    }
                    return '-';
                })
                ->addColumn('notes', function ($jo) {
                    if ($jo->notes) {
                        return '<span data-bs-toggle="tooltip" title="' . htmlspecialchars($jo->notes) . '">' . \Str::limit($jo->notes, 30) . '</span>';
                    }
                    return '-';
                })
                ->addColumn('actions', function ($jo) {
                    $actions = '<div class="btn-group btn-group-sm" role="group">';

                    // View button
                    $actions .=
                        '<a href="' .
                        route('job-orders.show', $jo->id) .
                        '" class="btn btn-sm btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>';

                    // Edit button
                    $actions .=
                        '<a href="' .
                        route('job-orders.edit', $jo->id) .
                        '" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>';

                    // Delete button
                    $actions .=
                        '<form action="' .
                        route('job-orders.destroy', $jo->id) .
                        '" method="POST" class="d-inline">
                                    ' .
                        csrf_field() .
                        '
                                    ' .
                        method_field('DELETE') .
                        '
                                    <button type="button" class="btn btn-sm btn-danger btn-delete" title="Delete">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>';

                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['description', 'notes', 'actions'])
                ->make(true);
        }

        // Get filter data untuk dropdown
        $projects = Project::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('name')->get(['id', 'name']);

        return view('production.job-orders.index', compact('projects', 'departments'));
    }

    // CREATE - Form tambah job order
    public function create()
    {
        $projects = Project::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('name')->get(['id', 'name']);

        return view('production.job-orders.create', compact('projects', 'departments'));
    }

    // STORE - Simpan job order baru
    public function store(Request $request)
    {
        // Validasi
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'department_id' => 'required|exists:departments,id',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        // ID sudah otomatis digenerate oleh model
        $validated['created_by'] = Auth::id();

        // Simpan
        $jobOrder = JobOrder::create($validated);

        return redirect()
            ->route('job-orders.index')
            ->with('success', 'Job Order berhasil dibuat: ' . $jobOrder->name);
    }

    // SHOW - Tampilkan detail
    public function show($id)
    {
        $jobOrder = JobOrder::with(['project', 'department', 'creator'])->findOrFail($id);
        return view('production.job-orders.show', compact('jobOrder'));
    }

    // EDIT - Form edit
    public function edit($id)
    {
        $jobOrder = JobOrder::findOrFail($id);
        $projects = Project::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('name')->get(['id', 'name']);

        return view('production.job-orders.edit', compact('jobOrder', 'projects', 'departments'));
    }

    // UPDATE - Update job order
    public function update(Request $request, $id)
    {
        $jobOrder = JobOrder::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'department_id' => 'required|exists:departments,id',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        $jobOrder->update($validated);

        return redirect()
            ->route('job-orders.index')
            ->with('success', 'Job Order berhasil diperbarui: ' . $jobOrder->id);
    }

    // DESTROY - Hapus PERMANEN job order
    public function destroy($id)
    {
        $jobOrder = JobOrder::findOrFail($id);
        $jobId = $jobOrder->id;
        $jobOrder->delete();

        return redirect()
            ->route('job-orders.index')
            ->with('success', 'Job Order berhasil dihapus: ' . $jobId);
    }

    // API untuk get job orders by project (untuk dropdown)
    public function getByProject($projectId)
    {
        $jobOrders = JobOrder::where('project_id', $projectId)
            ->orderBy('name')
            ->get(['id', 'name', 'start_date', 'end_date']);

        return response()->json($jobOrders);
    }

    // EXPORT - Export job orders ke Excel dengan filter
    public function export(Request $request)
    {
        $query = JobOrder::with(['project:id,name', 'department:id,name', 'creator:id,username'])->latest();

        // Apply same filters as index
        if ($request->filled('project')) {
            $query->where('project_id', $request->project);
        }

        if ($request->filled('department')) {
            $query->where('department_id', $request->department);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('id', 'like', "%{$searchTerm}%")
                    ->orWhere('name', 'like', "%{$searchTerm}%")
                    ->orWhereHas('project', function ($subQ) use ($searchTerm) {
                        $subQ->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('department', function ($subQ) use ($searchTerm) {
                        $subQ->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        $jobOrders = $query->get();

        // Generate filename dengan filter info
        $filename = 'job_orders_' . now()->format('Y-m-d_His');
        if ($request->filled('project')) {
            $project = Project::find($request->project);
            $filename .= '_project_' . str_replace(' ', '_', $project->name ?? 'unknown');
        }
        if ($request->filled('department')) {
            $dept = Department::find($request->department);
            $filename .= '_dept_' . str_replace(' ', '_', $dept->name ?? 'unknown');
        }
        $filename .= '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\JobOrderExport($jobOrders), $filename);
    }

    /**
     * Sync job orders from Lark Base
     * Following iSyment pattern: Controller as trigger, Service handles logic
     */
    public function syncFromLark(LarkJobOrderSyncService $syncService)
    {
        try {
            $stats = $syncService->sync();

            $message = sprintf('Lark sync completed! Fetched: %d | Created: %d | Updated: %d | Deactivated: %d', $stats['fetched'], $stats['created'], $stats['updated'], $stats['deactivated']);

            if ($stats['errors'] > 0) {
                $message .= sprintf(' | Errors: %d', $stats['errors']);
                return redirect()->route('job-orders.index')->with('warning', $message);
            }

            return redirect()->route('job-orders.index')->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Lark job order sync failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('job-orders.index')
                ->withErrors(['error' => 'Sync failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Get raw Lark response for debugging
     * Only accessible by super admin
     */
    public function getLarkRawData(LarkJobOrderSyncService $syncService)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }

        try {
            $rawData = $syncService->getRawResponse();

            return response()->json([
                'success' => true,
                'data' => $rawData,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }
}
