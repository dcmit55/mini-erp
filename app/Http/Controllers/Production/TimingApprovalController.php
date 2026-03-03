<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Production\Timing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TimingApprovalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display timing sessions pending approval
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Timing::with(['employee.department', 'project', 'jobOrder', 'approver'])->whereNotNull('end_time'); // Show all finished sessions (regardless of status field)

            // Filter by approval status
            if ($request->has('approval_status') && $request->approval_status !== '') {
                $query->where('approval_status', $request->approval_status);
            } else {
                // Default to pending only
                $query->where('approval_status', 'pending');
            }

            // Filter by project
            if ($request->has('project_id') && $request->project_id) {
                $query->where('project_id', $request->project_id);
            }

            // Filter by department
            if ($request->has('department_id') && $request->department_id) {
                $query->whereHas('employee', function ($q) use ($request) {
                    $q->where('department_id', $request->department_id);
                });
            }

            // Filter by date range
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('tanggal', '>=', $request->date_from);
            }
            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('tanggal', '<=', $request->date_to);
            }

            $query->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc');

            return DataTables::of($query)
                ->addColumn('checkbox', function ($timing) {
                    if ($timing->isPending()) {
                        return '<input type="checkbox" class="timing-checkbox" value="' . $timing->id . '">';
                    }
                    return '';
                })
                ->addColumn('employee_info', function ($timing) {
                    $dept = $timing->employee->department->name ?? 'N/A';
                    return '<strong>' . $timing->employee->name . '</strong><br><small class="text-muted">' . $dept . '</small>';
                })
                ->addColumn('project_info', function ($timing) {
                    $project = $timing->project->name ?? 'N/A';
                    $jobOrder = $timing->jobOrder->name ?? 'N/A';
                    return '<strong>' . $project . '</strong><br><small class="text-muted">JO: ' . $jobOrder . '</small>';
                })
                ->addColumn('work_details', function ($timing) {
                    $step = $timing->step ? '<span class="badge bg-info">' . $timing->step . '</span>' : '';
                    $parts = $timing->parts ? '<span class="badge bg-secondary">' . $timing->parts . '</span>' : '';
                    return $step . ' ' . $parts;
                })
                ->addColumn('duration_info', function ($timing) {
                    return '<strong>' .
                        $timing->duration_formatted .
                        '</strong><br>
                            <small class="text-muted">' .
                        $timing->start_time .
                        ' - ' .
                        $timing->end_time .
                        '</small>';
                })
                ->addColumn('output_info', function ($timing) {
                    $value = $timing->measurement_value ?? 0;
                    $type = $timing->measurement_type ?? 'pcs';
                    $efficiency = $timing->efficiency ?? 0;
                    return '<strong>' .
                        number_format($value, 0) .
                        '</strong> ' .
                        $type .
                        '<br>
                            <small class="text-success">Eff: ' .
                        number_format($efficiency, 2) .
                        '/hr</small>';
                })
                ->addColumn('approval_status_badge', function ($timing) {
                    $badges = [
                        'pending' => '<span class="badge bg-warning">Pending</span>',
                        'approved' => '<span class="badge bg-success">Approved</span>',
                        'rejected' => '<span class="badge bg-danger">Rejected</span>',
                    ];
                    return $badges[$timing->approval_status] ?? '<span class="badge bg-secondary">Unknown</span>';
                })
                ->addColumn('approver_info', function ($timing) {
                    if ($timing->approved_by && $timing->approver) {
                        return '<small>' . $timing->approver->name . '<br>' . $timing->approved_at->format('d M Y H:i') . '</small>';
                    }
                    return '<small class="text-muted">-</small>';
                })
                ->addColumn('actions', function ($timing) {
                    $buttons = '';

                    // Edit button - always available
                    $buttons .=
                        '<a href="' .
                        route('timing-approval.edit', $timing->id) .
                        '" class="btn btn-sm btn-info me-1" title="Edit Timing Data">
                                    <i class="bi bi-pencil-square"></i>
                                </a>';

                    if ($timing->isPending()) {
                        $buttons .=
                            '<button class="btn btn-sm btn-success me-1 btn-approve" data-id="' .
                            $timing->id .
                            '" title="Approve">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger btn-reject" data-id="' .
                            $timing->id .
                            '" title="Reject">
                                        <i class="bi bi-x-circle"></i>
                                    </button>';
                    } else {
                        $buttons .=
                            '<button class="btn btn-sm btn-secondary btn-view-reason" data-id="' .
                            $timing->id .
                            '" data-reason="' .
                            htmlspecialchars($timing->rejection_reason ?? '') .
                            '" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </button>';
                    }
                    return $buttons;
                })
                ->rawColumns(['checkbox', 'employee_info', 'project_info', 'work_details', 'duration_info', 'output_info', 'approval_status_badge', 'approver_info', 'actions'])
                ->make(true);
        }

        // Get filter options
        $projects = \App\Models\Production\Project::orderBy('name')->get();
        $departments = \App\Models\Admin\Department::orderBy('name')->get();

        // Get statistics
        $stats = [
            'pending' => Timing::pending()->count(),
            'approved_today' => Timing::approved()->whereDate('approved_at', today())->count(),
            'rejected_today' => Timing::rejected()->whereDate('approved_at', today())->count(),
        ];

        return view('production.timing-approval.index', compact('projects', 'departments', 'stats'));
    }

    /**
     * Approve a timing session
     */
    public function approve(Request $request, $id)
    {
        $timing = Timing::findOrFail($id);

        if (!$timing->isPending()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Only pending timings can be approved',
                ],
                400,
            );
        }

        DB::beginTransaction();
        try {
            $timing->approve(auth()->id());
            DB::commit();

            // Add session flash message
            session()->flash('success', 'Timing session approved successfully');

            return response()->json([
                'success' => true,
                'message' => 'Timing session approved successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error approving timing: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Reject a timing session
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $timing = Timing::findOrFail($id);

        if (!$timing->isPending()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Only pending timings can be rejected',
                ],
                400,
            );
        }

        DB::beginTransaction();
        try {
            $timing->reject(auth()->id(), $request->reason);
            DB::commit();

            // Add session flash message
            session()->flash('success', 'Timing session rejected successfully');

            return response()->json([
                'success' => true,
                'message' => 'Timing session rejected successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error rejecting timing: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Bulk approve timing sessions
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'timing_ids' => 'required|array|min:1',
            'timing_ids.*' => 'required|integer|exists:timings,id',
        ]);

        $timingIds = $request->timing_ids;

        // Handle JSON string
        if (is_string($timingIds)) {
            $timingIds = json_decode($timingIds, true);
        }

        DB::beginTransaction();
        try {
            $timings = Timing::whereIn('id', $timingIds)->where('approval_status', 'pending')->get();

            if ($timings->isEmpty()) {
                DB::rollBack();
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'No pending timings found for approval',
                    ],
                    400,
                );
            }

            foreach ($timings as $timing) {
                $timing->approve(auth()->id());
            }

            DB::commit();

            // Add session flash message
            session()->flash('success', 'Successfully approved ' . $timings->count() . ' timing session(s)');

            return response()->json([
                'success' => true,
                'message' => 'Successfully approved ' . $timings->count() . ' timing session(s)',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error in bulk approval: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Bulk reject timing sessions
     */
    public function bulkReject(Request $request)
    {
        $request->validate([
            'timing_ids' => 'required|array|min:1',
            'timing_ids.*' => 'required|integer|exists:timings,id',
            'reason' => 'required|string|max:500',
        ]);

        $timingIds = $request->timing_ids;

        // Handle JSON string
        if (is_string($timingIds)) {
            $timingIds = json_decode($timingIds, true);
        }

        DB::beginTransaction();
        try {
            $timings = Timing::whereIn('id', $timingIds)->where('approval_status', 'pending')->get();

            if ($timings->isEmpty()) {
                DB::rollBack();
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'No pending timings found for rejection',
                    ],
                    400,
                );
            }

            foreach ($timings as $timing) {
                $timing->reject(auth()->id(), $request->reason);
            }

            DB::commit();

            // Add session flash message
            session()->flash('success', 'Successfully rejected ' . $timings->count() . ' timing session(s)');

            return response()->json([
                'success' => true,
                'message' => 'Successfully rejected ' . $timings->count() . ' timing session(s)',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error in bulk rejection: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Edit timing from approval page
     */
    public function edit($id)
    {
        $timing = Timing::with(['project', 'employee.department', 'jobOrder'])->findOrFail($id);

        $projects = \App\Models\Production\Project::orderBy('name')->get();
        $jobOrders = \App\Models\Production\JobOrder::orderBy('name')->get();
        $employees = \App\Models\Hr\Employee::with('department')->orderBy('name')->get();

        return view('production.timing-approval.edit', compact('timing', 'projects', 'jobOrders', 'employees'));
    }

    /**
     * Update timing from approval page
     */
    public function update(Request $request, $id)
    {
        $timing = Timing::findOrFail($id);

        $request->validate([
            'tanggal' => 'required|date',
            'project_id' => 'required|exists:projects,id',
            'job_order_id' => 'nullable|exists:job_orders,id',
            'employee_id' => 'required|exists:employees,id',
            'step' => 'nullable|string|max:255',
            'parts' => 'nullable|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'measurement_value' => 'nullable|numeric|min:0',
            'measurement_type' => 'nullable|string|max:50',
            'remarks' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Calculate duration
            $start = \Carbon\Carbon::parse($request->tanggal . ' ' . $request->start_time);
            $end = \Carbon\Carbon::parse($request->tanggal . ' ' . $request->end_time);
            $durationMinutes = $start->diffInMinutes($end);

            $timing->update([
                'tanggal' => $request->tanggal,
                'project_id' => $request->project_id,
                'job_order_id' => $request->job_order_id,
                'employee_id' => $request->employee_id,
                'step' => $request->step,
                'parts' => $request->parts,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'duration_minutes' => $durationMinutes,
                'measurement_value' => $request->measurement_value,
                'measurement_type' => $request->measurement_type,
                'remarks' => $request->remarks,
            ]);

            DB::commit();

            return redirect()->route('timing-approval.index')->with('success', 'Timing data updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Error updating timing: ' . $e->getMessage()]);
        }
    }
}
