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
            $approvalStatus = $request->filled('approval_status') ? $request->approval_status : null;

            $query = Timing::with(['employee.department', 'project', 'jobOrder', 'approver'])->where('status', '!=', 'frozen');

            // For 'paused' filter — show only paused sessions
            if ($approvalStatus === 'paused') {
                $query->where('status', 'paused');
            } elseif ($approvalStatus === 'approved' || $approvalStatus === 'rejected') {
                // Approved/Rejected: sessions with end_time OR paused sessions that were processed
                $query->where('approval_status', $approvalStatus);
            } else {
                // All Status or pending: include completed/paused/orphaned sessions
                $query->where(function ($q) {
                    $q->whereNotNull('end_time')
                        ->orWhere('status', 'paused')
                        ->orWhere(function ($q2) {
                            $q2->where('status', 'on progress')->where('tanggal', '<', now()->toDateString());
                        });
                });

                if ($approvalStatus === 'pending') {
                    $query->where('approval_status', 'pending');
                }
                // $approvalStatus === null → no approval_status filter (show all)
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

            // Filter by employee
            if ($request->has('employee_id') && $request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }

            // Filter by date range
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('tanggal', '>=', $request->date_from);
            }
            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('tanggal', '<=', $request->date_to);
            }

            // Global search (DataTables search box)
            $search = $request->input('search.value');
            if ($search && trim($search) !== '') {
                $search = trim($search);
                $query->where(function ($q) use ($search) {
                    $q->whereHas('employee', function ($eq) use ($search) {
                        $eq->where('name', 'like', "%{$search}%");
                    })
                        ->orWhereHas('project', function ($pq) use ($search) {
                            $pq->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('jobOrder', function ($jq) use ($search) {
                            $jq->where('name', 'like', "%{$search}%");
                        })
                        ->orWhere('step', 'like', "%{$search}%")
                        ->orWhere('parts', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%");
                });
            }

            $query->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc');

            return DataTables::of($query)
                ->addColumn('tanggal_formatted', function ($timing) {
                    return $timing->tanggal ? $timing->tanggal->format('d-m-Y') : '-';
                })
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
                    // For still-running sessions, calculate elapsed time live
                    if (is_null($timing->end_time) && $timing->start_time) {
                        try {
                            $start = \Carbon\Carbon::parse($timing->tanggal->format('Y-m-d') . ' ' . $timing->start_time);
                            $elapsedMinutes = (int) $start->diffInMinutes(now());
                            $h = intdiv($elapsedMinutes, 60);
                            $m = $elapsedMinutes % 60;
                            $durationStr = sprintf('%02d:%02d', $h, $m) . ' <span class="badge bg-warning text-dark">RUNNING</span>';
                        } catch (\Exception $e) {
                            $durationStr = '-- <span class="badge bg-warning text-dark">RUNNING</span>';
                        }
                    } else {
                        $durationStr = $timing->duration_formatted;
                    }
                    return '<strong>' .
                        $durationStr .
                        '</strong><br>
                            <small class="text-muted">' .
                        $timing->start_time .
                        ' - ' .
                        ($timing->end_time ?? '<em>still running</em>') .
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
                    $isPaused = $timing->status === 'paused';
                    $badges = [
                        'pending' => '<span class="badge bg-warning">Pending</span>',
                        'approved' => '<span class="badge bg-success">Approved</span>',
                        'rejected' => '<span class="badge bg-danger">Rejected</span>',
                    ];
                    $badge = $badges[$timing->approval_status] ?? '<span class="badge bg-secondary">Unknown</span>';
                    if ($isPaused) {
                        $badge .= ' <span class="badge bg-info">Paused</span>';
                    }
                    return $badge;
                })
                ->addColumn('approver_info', function ($timing) {
                    if ($timing->approved_by && $timing->approver) {
                        try {
                            $approvedAtStr = $timing->approved_at ? \Carbon\Carbon::parse($timing->approved_at)->format('d M Y H:i') : '-';
                        } catch (\Exception $e) {
                            $approvedAtStr = '-';
                        }
                        return '<small>' . $timing->approver->name . '<br>' . $approvedAtStr . '</small>';
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
                ->rawColumns(['checkbox', 'tanggal_formatted', 'employee_info', 'project_info', 'work_details', 'duration_info', 'output_info', 'approval_status_badge', 'approver_info', 'actions'])
                ->make(true);
        }

        // Get filter options
        $projects = \App\Models\Production\Project::orderBy('name')->get();
        $departments = \App\Models\Admin\Department::orderBy('name')->get();
        $employees = \App\Models\Hr\Employee::where('status', 'active')->orderBy('name')->get();
        // Get statistics
        $stats = [
            'pending' => Timing::pending()->count(),
            'paused' => Timing::paused()->where('approval_status', 'pending')->count(),
            'approved_today' => Timing::approved()->whereDate('approved_at', today())->count(),
            'rejected_today' => Timing::rejected()->whereDate('approved_at', today())->count(),
        ];

        // Build available filter statuses from actual DB data
        $availableStatuses = [];
        $dbStatuses = Timing::whereNotNull('end_time')->whereNotNull('approval_status')->distinct()->pluck('approval_status')->toArray();
        foreach (['pending', 'approved', 'rejected'] as $s) {
            if (in_array($s, $dbStatuses)) {
                $availableStatuses[] = $s;
            }
        }
        // Insert 'paused' after 'pending' if any paused sessions exist
        if (Timing::where('status', 'paused')->exists()) {
            $pendingIdx = array_search('pending', $availableStatuses);
            if ($pendingIdx !== false) {
                array_splice($availableStatuses, $pendingIdx + 1, 0, ['paused']);
            } else {
                array_unshift($availableStatuses, 'paused');
            }
        }

        return view('production.timing-approval.index', compact('projects', 'departments', 'employees', 'stats', 'availableStatuses'));
    }

    /**
     * Approve a timing session
     */
    public function approve(Request $request, $id)
    {
        $timing = Timing::findOrFail($id);

        if (!$timing->isPending() && !$timing->isPaused()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Only pending or paused timings can be approved',
                ],
                400,
            );
        }

        DB::beginTransaction();
        try {
            // If session was paused, mark as complete on approve
            if ($timing->isPaused()) {
                $timing->status = 'complete';
            }
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

        if (!$timing->isPending() && !$timing->isPaused()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Only pending or paused timings can be rejected',
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
                // If paused, mark as complete on approval
                if ($timing->isPaused()) {
                    $timing->status = 'complete';
                }
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
        $jobOrders = \App\Models\Production\JobOrder::where(function ($q) {
            $q->whereNull('status')->orWhere('status', 'not like', '%deliver%');
        })
            ->orderBy('name')
            ->get();
        $employees = \App\Models\Hr\Employee::with('department')->orderBy('name')->get();

        return view('production.timing-approval.edit', compact('timing', 'projects', 'jobOrders', 'employees'));
    }

    /**
     * Update timing from approval page
     * Supports multiple employee_ids: updates the existing record for the first,
     * creates new records for any additional employees with the same timing data.
     */
    public function update(Request $request, $id)
    {
        $timing = Timing::findOrFail($id);

        $request->validate([
            'tanggal' => 'required|date',
            'project_id' => 'required|exists:projects,id',
            'job_order_id' => 'nullable|exists:job_orders,id',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'required|exists:employees,id',
            'step' => 'nullable|string|max:255',
            'parts' => 'nullable|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'measurement_value' => 'nullable|numeric|min:0',
            'measurement_type' => 'nullable|string|max:50',
            'remarks' => 'nullable|string',
            'break_deducted_minutes' => 'nullable|integer|min:0|max:480',
        ]);

        DB::beginTransaction();
        try {
            // Calculate duration: gross - manual break deduction
            // total_paused_minutes (app pause) is kept untouched; only break_deducted_minutes is overridden here
            $start = \Carbon\Carbon::parse($request->tanggal . ' ' . $request->start_time);
            $end = \Carbon\Carbon::parse($request->tanggal . ' ' . $request->end_time);
            $breakDeducted = (int) ($request->break_deducted_minutes ?? ($timing->break_deducted_minutes ?? 0));
            $durationMinutes = max(0, $start->diffInMinutes($end) - $breakDeducted);

            $employeeIds = $request->employee_ids;
            $baseData = [
                'tanggal' => $request->tanggal,
                'project_id' => $request->project_id,
                'job_order_id' => $request->job_order_id,
                'step' => $request->step,
                'parts' => $request->parts,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'duration_minutes' => $durationMinutes,
                'measurement_value' => $request->measurement_value,
                'measurement_type' => $request->measurement_type,
                'remarks' => $request->remarks,
                'break_deducted_minutes' => $breakDeducted,
                // total_paused_minutes is NOT touched — preserved from original app pause data
            ];

            // Update the existing record with the first selected employee
            $timing->update(
                array_merge($baseData, [
                    'employee_id' => $employeeIds[0],
                ]),
            );

            // Create new records for any additional employees
            $createdCount = 0;
            for ($i = 1; $i < count($employeeIds); $i++) {
                Timing::create(
                    array_merge($baseData, [
                        'employee_id' => $employeeIds[$i],
                        'status' => $timing->status ?? 'done',
                        'approval_status' => 'pending',
                    ]),
                );
                $createdCount++;
            }

            DB::commit();

            $message = 'Timing data updated successfully.';
            if ($createdCount > 0) {
                $message .= " {$createdCount} additional record(s) created for other employees.";
            }

            return redirect()->route('timing-approval.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Error updating timing: ' . $e->getMessage()]);
        }
    }
}
