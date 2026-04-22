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
            $query = JobOrder::with(['project:id,name', 'department:id,name', 'departments:id,name', 'creator:id,username'])->latest();

            // Apply filters
            if ($request->filled('project')) {
                $query->where('project_id', $request->project);
            }

            if ($request->filled('department')) {
                // Filter by ANY department (primary OR in pivot table)
                $departmentId = $request->department;
                $query->where(function ($q) use ($departmentId) {
                    $q->where('department_id', $departmentId)->orWhereHas('departments', function ($dq) use ($departmentId) {
                        $dq->where('departments.id', $departmentId);
                    });
                });
            }

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
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
                    // Show all departments from pivot table
                    if ($jo->departments && $jo->departments->count() > 0) {
                        $deptNames = $jo->departments->pluck('name')->toArray();
                        $displayText = implode(', ', $deptNames);

                        // If more than 3 departments, show tooltip with truncation
                        if (count($deptNames) > 3) {
                            $shortText = implode(', ', array_slice($deptNames, 0, 2)) . '... +' . (count($deptNames) - 2);
                            return '<span data-bs-toggle="tooltip" title="' . htmlspecialchars($displayText) . '">' . $shortText . '</span>';
                        }
                        // Show all departments if 3 or less
                        return $displayText;
                    }

                    // Fallback to primary department if pivot is empty
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
                ->addColumn('countdown_display', function ($jo) {
                    // Priority: Show "Delivered" status if job is delivered
                    if ($jo->isDelivered()) {
                        return '<span class="badge bg-success" title="Job has been delivered"><i class="fas fa-check-circle me-1"></i>Delivered</span>';
                    }

                    if (!$jo->delivery_date) {
                        return '-';
                    }

                    $daysUntil = $jo->days_until_delivery;

                    if ($daysUntil === null) {
                        return '<span class="text-muted">' . $jo->delivery_date->format('Y-m-d') . '</span>';
                    }

                    // Overdue
                    if ($daysUntil < 0) {
                        $overdueDays = abs($daysUntil);
                        return '<span class="badge bg-dark" title="Overdue by ' . $overdueDays . ' days"><i class="fas fa-times-circle me-1"></i>Overdue (' . $overdueDays . 'd)</span>';
                    }

                    // Today
                    if ($daysUntil === 0) {
                        return '<span class="badge bg-danger" title="Delivery today!"><i class="fas fa-exclamation-triangle me-1"></i>Today</span>';
                    }

                    $displayText = $daysUntil . ' days left';

                    // Warning badges for urgent deliveries
                    if ($daysUntil == 1) {
                        return '<span class="badge bg-danger" title="Urgent: 1 day left!"><i class="fas fa-exclamation-triangle me-1"></i>' . $displayText . '</span>';
                    } elseif ($daysUntil == 2) {
                        return '<span class="badge bg-warning text-dark" title="Warning: 2 days left"><i class="fas fa-exclamation-circle me-1"></i>' . $displayText . '</span>';
                    } elseif ($daysUntil <= 5) {
                        return '<span class="badge bg-info" title="' . $daysUntil . ' days remaining">' . $displayText . '</span>';
                    }

                    return '<span class="text-muted">' . $displayText . '</span>';
                })
                ->addColumn('actions', function ($jo) {
                    $imgUrl = $jo->hasFinalImage() ? e($jo->final_image_url) : '';
                    $imgName = e($jo->name);

                    $btnStyle = 'width:100%;padding:3px 0;font-size:12px;border-radius:4px;';

                    // Image button: biru full jika ada gambar, outline kosong jika tidak
                    if ($jo->hasFinalImage()) {
                        $imgBtn = '<button type="button" class="btn btn-sm btn-info btn-show-image" style="' . $btnStyle . '" title="View Final Image" data-img="' . $imgUrl . '" data-name="' . $imgName . '"><i class="bi bi-file-earmark-image"></i></button>';
                    } else {
                        $imgBtn = '<button type="button" class="btn btn-sm btn-outline-secondary" style="' . $btnStyle . '" title="No image" disabled><i class="bi bi-file-earmark-image"></i></button>';
                    }

                    $isGeneral = !auth()->user()->can('production.jo.edit');

                    if ($isGeneral) {
                        return '<div style="display:grid;grid-template-columns:1fr 1fr;gap:3px;min-width:70px;">' . '<a href="' . route('job-orders.show', $jo->id) . '" class="btn btn-sm btn-info" style="' . $btnStyle . '" title="Detail"><i class="bi bi-eye"></i></a>' . $imgBtn . '</div>';
                    }

                    return '<div style="display:grid;grid-template-columns:1fr 1fr;gap:3px;min-width:70px;">' . '<a href="' . route('job-orders.show', $jo->id) . '" class="btn btn-sm btn-info" style="' . $btnStyle . '" title="Detail"><i class="bi bi-eye"></i></a>' . $imgBtn . '<a href="' . route('job-orders.edit', $jo->id) . '" class="btn btn-sm btn-warning" style="' . $btnStyle . '" title="Edit"><i class="bi bi-pencil"></i></a>' . '<form action="' . route('job-orders.destroy', $jo->id) . '" method="POST" style="display:contents;">' . csrf_field() . method_field('DELETE') . '<button type="button" class="btn btn-sm btn-danger btn-delete" style="' . $btnStyle . '" title="Delete"><i class="bi bi-trash3"></i></button>' . '</form>' . '</div>';
                })
                ->rawColumns(['description', 'notes', 'countdown_display', 'department_name', 'actions'])
                ->make(true);
        }

        // Get filter data untuk dropdown
        $projects = Project::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('name')->get(['id', 'name']);

        // Get distinct status values from database
        $statuses = JobOrder::select('status')->distinct()->whereNotNull('status')->orderBy('status')->pluck('status');

        return view('production.job-orders.index', compact('projects', 'departments', 'statuses'));
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
            'department_ids' => 'nullable|array', // NEW: Multiple departments
            'department_ids.*' => 'exists:departments,id',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'total_standard_minutes' => 'nullable|integer|min:0',
            'standard_time_per_unit' => 'nullable|numeric|min:0',
        ]);

        // ID sudah otomatis digenerate oleh model
        $validated['created_by'] = Auth::id();

        // Extract department_ids untuk pivot sync
        $departmentIds = $validated['department_ids'] ?? [];
        unset($validated['department_ids']);

        // Simpan
        $jobOrder = JobOrder::create($validated);

        // Sync multiple departments via pivot table
        if (!empty($departmentIds)) {
            $jobOrder->departments()->sync($departmentIds);
        }

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
        $jobOrder = JobOrder::with('departments')->findOrFail($id);
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
            'department_ids' => 'nullable|array', // NEW: Multiple departments
            'department_ids.*' => 'exists:departments,id',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'total_standard_minutes' => 'nullable|integer|min:0',
            'standard_time_per_unit' => 'nullable|numeric|min:0',
        ]);

        // Extract department_ids untuk pivot sync
        $departmentIds = $validated['department_ids'] ?? [];
        unset($validated['department_ids']);

        $jobOrder->update($validated);

        // Sync multiple departments via pivot table
        if (!empty($departmentIds)) {
            $jobOrder->departments()->sync($departmentIds);
        } else {
            // If no additional departments selected, detach all
            $jobOrder->departments()->detach();
        }

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
        if (!auth()->user()->can('production.jo.delete')) {
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
