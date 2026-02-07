<?php

namespace App\Http\Controllers\Logistic;

use App\Http\Controllers\Controller;
use App\Models\Logistic\MaterialRequest;
use App\Models\Logistic\Inventory;
use App\Models\Production\Project;
use App\Models\Admin\Department;
use App\Models\Logistic\Unit;
use App\Rules\ValidProjectSource;
use Illuminate\Http\Request;
use App\Events\MaterialRequestUpdated;
use App\Models\Admin\User;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MaterialRequestExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Cache;

class MaterialRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = MaterialRequest::with(['inventory:id,name,quantity,unit', 'project:id,name,department_id', 'jobOrder:id,name,project_id', 'user:id,username,department_id', 'user.department:id,name'])->latest();

            // Apply filters with null checks
            if ($request->filled('project')) {
                $query->where('project_id', $request->project);
            }
            if ($request->filled('job_order')) {
                $query->where('job_order_id', $request->job_order);
            }
            if ($request->filled('material')) {
                $query->where('inventory_id', $request->material);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('requested_by')) {
                $query->where('requested_by', $request->requested_by);
            }
            if ($request->filled('requested_at')) {
                $query->whereDate('created_at', $request->requested_at);
            }

            // Add custom search functionality like inventory
            if ($request->filled('custom_search')) {
                $search = $request->custom_search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('inventory', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                        ->orWhereHas('project', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        })
                        ->orWhere('requested_by', 'like', "%{$search}%")
                        ->orWhere('remark', 'like', "%{$search}%");
                });
            }

            return DataTables::of($query)
                ->addColumn('checkbox', function ($req) {
                    return $req->status === 'approved' ? '<input type="checkbox" class="select-row" value="' . $req->id . '">' : '';
                })
                ->addColumn('project_name', function ($req) {
                    return $req->project->name ?? '(No Project)';
                })
                ->addColumn('job_order', function ($req) {
                    return $req->jobOrder ? $req->jobOrder->name : '-';
                })
                ->addColumn('material_name', function ($req) {
                    return '<span class="material-detail-link gradient-link" data-id="' . ($req->inventory->id ?? '') . '">' . ($req->inventory->name ?? '(No Material)') . '</span>';
                })
                ->addColumn('requested_qty', function ($req) {
                    return rtrim(rtrim(number_format($req->qty, 2, '.', ''), '0'), '.') . ' ' . ($req->inventory->unit ?? '');
                })
                ->addColumn('remaining_qty', function ($req) {
                    $remaining = $req->qty - $req->processed_qty;
                    return '<span data-bs-toggle="tooltip" title="' . ($req->inventory->unit ?? '') . '">' . rtrim(rtrim(number_format($remaining, 2, '.', ''), '0'), '.') . '</span>';
                })
                ->addColumn('processed_qty', function ($req) {
                    return '<span data-bs-toggle="tooltip" title="' . ($req->inventory->unit ?? '') . '">' . rtrim(rtrim(number_format($req->processed_qty, 2, '.', ''), '0'), '.') . '</span>';
                })
                ->addColumn('requested_by', function ($req) {
                    $department = $req->user && $req->user->department ? ucfirst($req->user->department->name) : '-';
                    return '<span data-bs-toggle="tooltip" title="' . $department . '">' . ucfirst($req->requested_by) . '</span>';
                })
                ->addColumn('requested_at', function ($req) {
                    return $req->created_at ? $req->created_at->format('Y-m-d, H:i') : '-';
                })
                ->addColumn('status', function ($req) {
                    $authUser = auth()->user();
                    $isAdmin = $authUser->isReadOnlyAdmin();
                    $isLogisticAdmin = $authUser->isLogisticAdmin();

                    // Show select for super_admin, admin_logistic, admin (but admin is disabled)
                    if ($isLogisticAdmin || $isAdmin) {
                        return '<select name="status" class="form-select form-select-sm status-select status-select-rounded status-quick-update"
                                data-id="' .
                            $req->id .
                            '" ' .
                            '>
                                <option value="pending" ' .
                            ($req->status === 'pending' ? 'selected' : '') .
                            '>Pending</option>
                                <option value="approved" ' .
                            ($req->status === 'approved' ? 'selected' : '') .
                            '>Approved</option>
                                <option value="canceled" ' .
                            ($req->status === 'canceled' ? 'selected' : '') .
                            '>Canceled</option>
                                <option value="delivered" ' .
                            ($req->status === 'delivered' ? 'selected' : '') .
                            ' disabled>Delivered</option>
                            </select>';
                    } else {
                        return '<span class="badge rounded-pill ' . $req->getStatusBadgeClass() . '">' . ucfirst($req->status) . '</span>';
                    }
                })
                ->addColumn('remark', function ($req) {
                    return $req->remark ?? '-';
                })
                ->addColumn('actions', function ($req) {
                    $authUser = auth()->user();
                    $isLogisticAdmin = $authUser->isLogisticAdmin();
                    $isSuperAdmin = $authUser->isSuperAdmin();
                    $isAdmin = $authUser->isReadOnlyAdmin();
                    $isRequestOwner = $authUser->username === $req->requested_by;

                    $actions = '<div class="d-flex flex-nowrap gap-1">';

                    // Goods Out Button
                    if ($req->status === 'approved' && $req->status !== 'canceled' && $req->qty - $req->processed_qty > 0 && ($isLogisticAdmin || $isAdmin)) {
                        $actions .=
                            '<a href="' .
                            route('goods_out.create_with_id', $req->id) .
                            '" class="btn btn-sm btn-success" title="Goods Out">
                                <i class="bi bi-box-arrow-right"></i>
                            </a>';
                    }

                    // Edit Button
                    if ($req->status === 'pending' && ($isRequestOwner || $isLogisticAdmin || $isAdmin)) {
                        $actions .=
                            '<a href="' .
                            route('material_requests.edit', $req->id) .
                            '" class="btn btn-sm btn-warning" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </a>';
                    }

                    // Delete Button
                    $canDelete = false;
                    $deleteTooltip = 'Delete';

                    if (in_array($req->status, ['approved', 'delivered'])) {
                        // Only super admin can delete approved/delivered requests
                        if ($isSuperAdmin || $isAdmin) {
                            $canDelete = true;
                            $deleteTooltip = 'Delete (Super Admin Only)';
                        }
                    } elseif ($req->status === 'pending') {
                        // Pending: Only Owner or Super Admin can delete
                        if ($isRequestOwner || $isSuperAdmin || $isAdmin) {
                            $canDelete = true;
                            $deleteTooltip = $isRequestOwner ? 'Delete Your Request' : 'Delete (Super Admin)';
                        }
                    } elseif ($req->status === 'canceled') {
                        // Canceled: Owner, Admin Logistic, or Super Admin can delete
                        if ($isRequestOwner || $isLogisticAdmin || $isSuperAdmin || $isAdmin) {
                            $canDelete = true;
                            if ($isRequestOwner) {
                                $deleteTooltip = 'Delete Your Canceled Request';
                            } elseif ($isLogisticAdmin) {
                                $deleteTooltip = 'Delete Canceled Request (Logistic Admin)';
                            } else {
                                $deleteTooltip = 'Delete Canceled Request (Super Admin)';
                            }
                        }
                    }

                    if ($canDelete) {
                        $actions .=
                            '<form action="' .
                            route('material_requests.destroy', $req->id) .
                            '" method="POST" class="delete-form" style="display:inline;">
                                <input type="hidden" name="_method" value="DELETE">
                                <input type="hidden" name="_token" value="' .
                            csrf_token() .
                            '">
                                <button type="button" class="btn btn-sm btn-danger btn-delete" title="' .
                            $deleteTooltip .
                            '">
                                            <i class="bi bi-trash3"></i>
                                </button>
                            </form>';
                    }

                    // Reminder Button
                    if (in_array($req->status, ['pending', 'approved']) && ($isRequestOwner || $isSuperAdmin)) {
                        $actions .=
                            '<button class="btn btn-sm btn-primary btn-reminder" data-id="' .
                            $req->id .
                            '" title="Remind Logistic">
                        <i class="bi bi-bell"></i>
                        </button>';
                    }

                    return $actions;
                })
                ->rawColumns(['checkbox', 'job_order', 'material_name', 'remaining_qty', 'processed_qty', 'requested_by', 'status', 'remark', 'actions'])
                ->setRowId(function ($req) {
                    return 'row-' . $req->id;
                })
                ->orderColumn('requested_at', 'created_at $1')
                ->make(true);
        }

        // For non-AJAX requests, return view with filter data only
        // Cache filter options for better performance
        $projects = Cache::remember('material_requests_projects', 300, function () {
            return Project::orderBy('name')->get(['id', 'name']);
        });

        $materials = Cache::remember('material_requests_materials', 300, function () {
            return Inventory::orderBy('name')->get(['id', 'name']);
        });

        $users = Cache::remember('material_requests_users', 300, function () {
            return User::orderBy('username')->get(['username']);
        });

        $jobOrders = Cache::remember('material_requests_job_orders', 300, function () {
            return \App\Models\Production\JobOrder::orderBy('id', 'desc')->get(['id', 'name']);
        });

        return view('logistic.material_requests.index', compact('projects', 'materials', 'users', 'jobOrders'));
    }

    public function export(Request $request)
    {
        // Ambil filter dari request
        $project = $request->project;
        $jobOrder = $request->job_order;
        $material = $request->material;
        $status = $request->status;
        $requestedBy = $request->requested_by;
        $requestedAt = $request->requested_at;

        // Filter data berdasarkan request
        $query = MaterialRequest::with(['inventory', 'project', 'jobOrder', 'user.department']);

        if ($project) {
            $query->where('project_id', $project);
        }

        if ($jobOrder) {
            $query->where('job_order_id', $jobOrder);
        }

        if ($material) {
            $query->where('inventory_id', $material);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($requestedBy) {
            $query->where('requested_by', $requestedBy);
        }

        if ($requestedAt) {
            $query->whereDate('created_at', $requestedAt);
        }

        // Order by created_at descending (newest first)
        $requests = $query->orderBy('created_at', 'desc')->get();

        // Buat nama file dinamis
        $fileName = 'material_requests';
        if ($project) {
            $projectName = Project::find($project)->name ?? 'Unknown Project';
            $fileName .= '_project-' . str_replace(' ', '-', strtolower($projectName));
        }
        if ($jobOrder) {
            $jobOrderName = \App\Models\Production\JobOrder::find($jobOrder)->name ?? 'Unknown JobOrder';
            $fileName .= '_joborder-' . str_replace(' ', '-', strtolower($jobOrderName));
        }
        if ($material) {
            $materialName = Inventory::find($material)->name ?? 'Unknown Material';
            $fileName .= '_material-' . str_replace(' ', '-', strtolower($materialName));
        }
        if ($status) {
            $fileName .= '_status-' . strtolower($status);
        }
        if ($requestedBy) {
            $fileName .= '_requested_by-' . strtolower($requestedBy);
        }
        if ($requestedAt) {
            $fileName .= '_requested_at-' . $requestedAt;
        }
        $fileName .= '_' . now()->format('Y-m-d') . '.xlsx';

        // Ekspor data menggunakan kelas MaterialRequestExport
        return Excel::download(new MaterialRequestExport($requests), $fileName);
    }

    public function create(Request $request)
    {
        $inventories = Inventory::orderBy('name')->get();
        // DATA GOVERNANCE: Hanya ambil project dari Lark (created_by = 'Sync from Lark')
        // Legacy projects TIDAK ditampilkan sama sekali di dropdown
        $projects = Project::fromLark()->with('departments', 'status')->notArchived()->orderBy('name')->get();

        // Ambil job orders untuk dropdown
        $jobOrders = \App\Models\Production\JobOrder::with('project:id,name')
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'project_id']);

        $departments = Department::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        // Periksa apakah parameter material_id ada
        $selectedMaterial = null;
        if ($request->has('material_id')) {
            $selectedMaterial = Inventory::find($request->material_id);
        }

        return view('logistic.material_requests.create', compact('inventories', 'projects', 'jobOrders', 'selectedMaterial', 'departments', 'units'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to create material requests.');
        }

        $request->validate([
            'inventory_id' => 'required|exists:inventories,id',
            'project_id' => ['required', 'exists:projects,id', new ValidProjectSource()],
            'job_order_id' => 'nullable|exists:job_orders,id',
            'qty' => 'required|numeric|min:0.01',
        ]);

        $user = Auth::user();
        $department = $user->department ? $user->department->name : null;

        DB::beginTransaction();
        try {
            // Lock inventory row
            $inventory = Inventory::where('id', $request->inventory_id)->lockForUpdate()->first();

            // Validasi stok
            if ($request->qty > $inventory->quantity) {
                DB::rollBack();
                return back()
                    ->withInput()
                    ->withErrors(['qty' => 'Requested quantity cannot exceed available inventory quantity.']);
            }

            $materialRequest = MaterialRequest::create([
                'inventory_id' => $request->inventory_id,
                'project_id' => $request->project_id,
                'job_order_id' => $request->job_order_id, // Tambahkan job_order_id
                'qty' => $request->qty,
                'requested_by' => $user->username,
                'remark' => $request->remark,
            ]);

            // (Opsional) Kurangi stok jika memang ingin langsung mengurangi
            // $inventory->quantity -= $request->qty;
            // $inventory->save();

            DB::commit();

            // Trigger event
            event(new MaterialRequestUpdated($materialRequest, 'created'));

            $project = Project::findOrFail($request->project_id);

            return redirect()
                ->route('material_requests.index')
                ->with('success', "Material Request for <b>{$inventory->name}</b> in project <b>{$project->name}</b> created successfully!");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['qty' => 'Failed to create request: ' . $e->getMessage()]);
        }
    }

    public function bulkCreate()
    {
        $inventories = Inventory::orderBy('name')->get();
        $projects = Project::with('departments', 'status')->notArchived()->orderBy('name')->get();
        $jobOrders = \App\Models\Production\JobOrder::with('project:id,name')
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'project_id']);
        $departments = Department::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        return view('logistic.material_requests.bulk_create', compact('inventories', 'projects', 'jobOrders', 'departments', 'units'));
    }

    public function bulkStore(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to create material requests.');
        }

        $request->validate([
            'requests.*.inventory_id' => 'required|exists:inventories,id',
            'requests.*.project_id' => 'required|exists:projects,id',
            'requests.*.job_order_id' => [
                'required',
                'exists:job_orders,id',
                function ($attribute, $value, $fail) use ($request) {
                    $index = explode('.', $attribute)[1];
                    $projectId = $request->input("requests.$index.project_id");
                    $jobOrder = \App\Models\Production\JobOrder::find($value);

                    if ($jobOrder && $jobOrder->project_id != $projectId) {
                        $fail('The selected job order does not belong to the selected project.');
                    }
                },
            ],
            'requests.*.qty' => 'required|numeric|min:0.01',
        ]);

        $user = Auth::user();
        $department = $user->department ? $user->department->name : null;

        $createdRequests = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($request->requests as $index => $req) {
                // Lock inventory row
                $inventory = Inventory::where('id', $req['inventory_id'])->lockForUpdate()->first();

                // Validasi stok
                if ($req['qty'] > $inventory->quantity) {
                    $errors["requests.$index.qty"] = "Quantity exceeds stock for '{$inventory->name}'.";
                } else {
                    $materialRequest = MaterialRequest::create([
                        'inventory_id' => $req['inventory_id'],
                        'project_id' => $req['project_id'],
                        'job_order_id' => $req['job_order_id'],
                        'qty' => $req['qty'],
                        'processed_qty' => 0,
                        'requested_by' => $user->username,
                        'remark' => $req['remark'] ?? null,
                    ]);
                    $createdRequests[] = $materialRequest;
                }
            }

            if (!empty($errors)) {
                DB::rollBack();
                return back()->withInput()->withErrors($errors);
            }

            DB::commit();

            // Trigger event SEKALI SAJA setelah commit
            if (!empty($createdRequests)) {
                event(new MaterialRequestUpdated($createdRequests, 'created'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['bulk' => 'Bulk request failed: ' . $e->getMessage()]);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Bulk material requests submitted!',
                'created_requests' => $createdRequests,
            ]);
        }

        $infoList = [];
        foreach ($createdRequests as $req) {
            $infoList[] = "<b>{$req->inventory->name}</b> in project <b>{$req->project->name}</b>";
        }
        $infoString = implode(', ', $infoList);

        return redirect()
            ->route('material_requests.index')
            ->with('success', "Bulk material requests submitted successfully for: {$infoString}");
    }

    public function edit(Request $request, $id)
    {
        $materialRequest = MaterialRequest::with('inventory', 'project')->findOrFail($id);
        $departments = Department::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        $filters = [
            'project' => $request->input('filter_project'),
            'material' => $request->input('filter_material'),
            'status' => $request->input('filter_status'),
            'requested_by' => $request->input('filter_requested_by'),
            'requested_at' => $request->input('filter_requested_at'),
        ];
        $filters = array_filter($filters, fn($v) => !is_null($v) && $v !== '');

        // Validasi: Pastikan hanya Material Request dengan status tertentu yang bisa diedit
        if ($materialRequest->status !== 'pending') {
            return redirect()->route('material_requests.index')->with('error', 'Only pending requests can be edited.');
        }

        // Validasi: Pastikan request bukan milik user lain kecuali admin logistic
        if (auth()->user()->username !== $materialRequest->requested_by && !auth()->user()->isLogisticAdmin() && !auth()->user()->isReadOnlyAdmin()) {
            return redirect()->route('material_requests.index', $filters)->with('error', 'You do not have permission to edit this request.');
        }

        // Validasi: Pastikan request bukan status canceled
        if ($materialRequest->status === 'canceled') {
            return redirect()->route('material_requests.index', $filters)->with('error', 'Canceled requests cannot be edited.');
        }

        // Validasi: Pastikan inventory dan project masih ada
        if (!$materialRequest->inventory || !$materialRequest->project) {
            return redirect()->route('material_requests.index', $filters)->with('error', 'The associated inventory or project no longer exists.');
        }

        $inventories = Inventory::orderBy('name')
            ->get()
            ->map(function ($inventory) {
                $inventory->available_quantity = $inventory->quantity;
                return $inventory;
            });

        $jobOrders = \App\Models\Production\JobOrder::with('project:id,name')
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'project_id']);

        return view('logistic.material_requests.edit', [
            'request' => $materialRequest,
            'inventories' => $inventories,
            'jobOrders' => $jobOrders,
            'departments' => $departments,
            'units' => $units,
        ]);
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to edit material requests.');
        }

        $materialRequest = MaterialRequest::findOrFail($id);

        // Jika status sudah delivered, tolak update status
        if ($materialRequest->status === 'delivered' && $request->has('status')) {
            return redirect()->route('material_requests.index')->with('error', 'Delivered requests cannot be updated.');
        }

        // Jika hanya status yang diperbarui (inline dari tabel)
        // Jika hanya status yang diperbarui (inline dari tabel)
        if ($request->has('status') && !$request->has('inventory_id')) {
            // Redirect ke quickUpdate method atau handle sendiri
            return $this->quickUpdate($request, $id);
        }

        // Validasi untuk pembaruan lengkap
        $request->validate([
            'inventory_id' => 'required|exists:inventories,id',
            'job_order_id' => 'required|exists:job_orders,id',
            'project_id' => 'required|exists:projects,id',
            'qty' => 'required|numeric|min:0.01',
            'status' => 'required|in:pending,approved,delivered,canceled',
            'remark' => 'nullable|string',
        ]);

        $filters = [
            'project' => $request->input('filter_project'),
            'material' => $request->input('filter_material'),
            'status' => $request->input('filter_status'),
            'requested_by' => $request->input('filter_requested_by'),
            'requested_at' => $request->input('filter_requested_at'),
        ];
        $filters = array_filter($filters, fn($v) => !is_null($v) && $v !== '');

        // Tidak boleh update jika status delivered/canceled
        if (in_array($materialRequest->status, ['delivered', 'canceled'])) {
            return redirect()->route('material_requests.index')->with('error', 'Delivered or canceled requests cannot be updated.');
        }

        DB::beginTransaction();
        try {
            // Lock inventory row
            $inventory = Inventory::where('id', $request->inventory_id)->lockForUpdate()->first();

            // Validasi stok
            if ($request->qty > $inventory->quantity) {
                DB::rollBack();
                return back()
                    ->withInput()
                    ->withErrors(['qty' => 'Requested quantity cannot exceed available inventory quantity.']);
            }

            // Siapkan data update
            $updateData = [
                'inventory_id' => $request->inventory_id,
                'job_order_id' => $request->job_order_id,
                'project_id' => $request->project_id,
                'qty' => $request->qty,
                'status' => $request->status,
                'remark' => $request->remark,
            ];
            // Set approved_at jika status berubah ke approved
            if ($request->status === 'approved' && $materialRequest->status !== 'approved') {
                $updateData['approved_at'] = now();
            }

            $materialRequest->update($updateData);

            DB::commit();

            // Trigger event
            event(new MaterialRequestUpdated($materialRequest, 'updated'));

            $project = Project::findOrFail($request->project_id);
            return redirect()
                ->route('material_requests.index', $filters)
                ->with('success', "Material Request for <b>{$inventory->name}</b> in project <b>{$project->name}</b> updated successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['qty' => 'Failed to update request: ' . $e->getMessage()]);
        }
    }

    public function quickUpdate(Request $request, $id)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'You do not have permission to update status.',
                ],
                403,
            );
        }

        $request->validate([
            'status' => 'required|in:pending,approved,delivered,canceled',
        ]);

        $materialRequest = MaterialRequest::findOrFail($id);

        // Jika status sudah delivered, tolak update
        if ($materialRequest->status === 'delivered') {
            return response()->json(['success' => false, 'message' => 'Delivered requests cannot be updated.'], 422);
        }

        // Check permissions
        if (!auth()->user()->isLogisticAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        // Ambil data sebelum update untuk event
        $oldStatus = $materialRequest->status;

        // Perbarui status
        $updateData = ['status' => $request->status];
        if ($request->status === 'approved' && $materialRequest->status !== 'approved') {
            $updateData['approved_at'] = now();
        }

        $materialRequest->update($updateData);

        // Trigger event untuk real-time update
        event(new MaterialRequestUpdated($materialRequest, 'status'));

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'old_status' => $oldStatus,
            'new_status' => $materialRequest->status,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to delete material requests.');
        }

        $materialRequest = MaterialRequest::findOrFail($id);

        $filters = [
            'project' => $request->input('filter_project'),
            'material' => $request->input('filter_material'),
            'status' => $request->input('filter_status'),
            'requested_by' => $request->input('filter_requested_by'),
            'requested_at' => $request->input('filter_requested_at'),
        ];
        $filters = array_filter($filters, fn($v) => !is_null($v) && $v !== '');

        // Check user permissions
        $authUser = auth()->user();
        $isSuperAdmin = $authUser->isSuperAdmin();
        $isLogisticAdmin = $authUser->isLogisticAdmin();
        $isRequestOwner = $authUser->username === $materialRequest->requested_by;

        // Validasi berdasarkan status dan role user
        if (in_array($materialRequest->status, ['approved', 'delivered'])) {
            // Hanya super admin yang bisa delete request dengan status approved/delivered
            if (!$isSuperAdmin) {
                return redirect()->route('material_requests.index', $filters)->with('error', 'Only Super Admin can delete approved or delivered requests.');
            }
        } elseif ($materialRequest->status === 'pending') {
            // Pending: Hanya Owner atau Super Admin yang bisa delete
            if (!$isRequestOwner && !$isSuperAdmin) {
                return redirect()->route('material_requests.index', $filters)->with('error', 'Only request owner or Super Admin can delete pending requests.');
            }
        } elseif ($materialRequest->status === 'canceled') {
            // Canceled: Owner, Admin Logistic, atau Super Admin bisa delete
            if (!$isRequestOwner && !$isLogisticAdmin && !$isSuperAdmin) {
                return redirect()->route('material_requests.index', $filters)->with('error', 'You do not have permission to delete this canceled request.');
            }
        }

        // Trigger event
        event(new MaterialRequestUpdated($materialRequest, 'deleted'));

        $materialRequest->delete();

        $inventory = $materialRequest->inventory ?? Inventory::find($materialRequest->inventory_id);
        $project = $materialRequest->project ?? Project::find($materialRequest->project_id);

        return redirect()
            ->route('material_requests.index', $filters)
            ->with('success', "Material Request for <b>{$inventory->name}</b> in project <b>{$project->name}</b> deleted successfully.");
    }

    public function sendReminder($id)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to send reminders material requests.');
        }

        $request = MaterialRequest::findOrFail($id);

        // Pastikan status pending atau approved dan belum delivered
        if (!in_array($request->status, ['pending', 'approved']) || $request->processed_qty >= $request->qty) {
            return response()->json(['success' => false, 'message' => 'Reminder not allowed for this request.']);
        }

        // Broadcast event ke admin logistic
        event(new \App\Events\MaterialRequestReminder($request));

        return response()->json(['success' => true]);
    }

    public function bulkDetails(Request $request)
    {
        $request->validate([
            'selected_ids' => 'required|array',
            'selected_ids.*' => 'exists:material_requests,id',
        ]);

        $requests = MaterialRequest::with('inventory', 'project', 'jobOrder')->whereIn('id', $request->selected_ids)->get();

        $data = $requests->map(function ($req) {
            return [
                'id' => $req->id,
                'material_name' => $req->inventory->name ?? '-',
                'unit' => $req->inventory->unit ?? '',
                'job_order_name' => $req->jobOrder->name ?? '-',
                'project_name' => $req->project->name ?? '-',
                'requested_by' => $req->requested_by,
                'requested_qty' => rtrim(rtrim(number_format($req->qty, 2, '.', ''), '0'), '.'),
                'remaining_qty' => rtrim(rtrim(number_format($req->remaining_qty, 2, '.', ''), '0'), '.'),
            ];
        });

        return response()->json($data);
    }

    public function storeFromPlanning($planning)
    {
        try {
            \Log::info('Processing material request from planning: ' . $planning->id . ' - ' . $planning->material_name);

            // Cek apakah material ada di inventory
            $inventoryId = \App\Models\Logistic\Inventory::where('name', $planning->material_name)->value('id');

            // Jika material tidak ada di inventory, buat dulu inventory baru
            if (!$inventoryId) {
                // Pastikan ada category default
                $defaultCategory = \App\Models\Logistic\Category::first();
                if (!$defaultCategory) {
                    $defaultCategory = \App\Models\Logistic\Category::create([
                        'name' => 'Default',
                        'description' => 'Default category for material planning',
                    ]);
                }

                $inventory = \App\Models\Logistic\Inventory::create([
                    'name' => $planning->material_name,
                    'quantity' => 0,
                    'unit' => optional($planning->unit)->name ?? 'pcs',
                    'category_id' => $defaultCategory->id,
                    'location' => 'Warehouse',
                    'min_stock_level' => 0,
                    'created_by' => 'system',
                ]);
                $inventoryId = $inventory->id;
                \Log::info('New inventory created: ' . $inventory->name . ' (ID: ' . $inventoryId . ')');
            }

            // Ambil user berdasarkan ID dari planning
            $user = \App\Models\Admin\User::find($planning->requested_by);
            if (!$user) {
                \Log::error('User not found with ID: ' . $planning->requested_by);
                return null;
            }

            $username = $user->username;
            \Log::info('User found - ID: ' . $planning->requested_by . ', Username: ' . $username);

            // Buat material request
            $materialRequest = \App\Models\Logistic\MaterialRequest::create([
                'inventory_id' => $inventoryId,
                'project_id' => $planning->project_id,
                'qty' => $planning->qty_needed,
                'processed_qty' => 0,
                'requested_by' => $username,
                'remark' => 'Imported from Material Planning',
                'status' => 'pending',
            ]);

            \Log::info('Material request successfully created with ID: ' . $materialRequest->id);

            return $materialRequest;
        } catch (\Exception $e) {
            \Log::error('Error creating material request: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }
}
