<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Supplier;
use App\Models\Procurement\LocationSupplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        // Only allowed roles can create/edit/delete
        $allowedRoles = ['super_admin', 'admin_procurement', 'admin_logistic', 'admin_finance', 'admin'];
        $writeMethods = ['create', 'store', 'edit', 'update', 'destroy', 'quickStore'];

        $this->middleware(function ($request, $next) use ($allowedRoles, $writeMethods) {
            $user = Auth::user();
            if (in_array($request->route()->getActionMethod(), $writeMethods) && !in_array($user->role, $allowedRoles)) {
                abort(403, 'You do not have permission to modify supplier data.');
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of the suppliers with Server-side processing
     */
    public function index(Request $request)
    {
        // Jika AJAX request, return DataTables data
        if ($request->ajax()) {
            return $this->getDataTablesData($request);
        }

        // Untuk non-AJAX requests, return view dengan master data untuk filters
        $locations = LocationSupplier::orderBy('name')->get();

        return view('procurement.suppliers.index', compact('locations'));
    }

    // Method untuk server-side processing
    private function getDataTablesData(Request $request)
    {
        $query = Supplier::with('location')->latest();

        // Apply filters
        if ($request->filled('location_filter')) {
            $query->where('location_id', $request->location_filter);
        }

        if ($request->filled('status_filter')) {
            $query->where('status', $request->status_filter);
        }

        // Custom search functionality - cari di multiple columns
        if ($request->filled('custom_search')) {
            $searchValue = $request->input('custom_search');
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'LIKE', '%' . $searchValue . '%')
                    ->orWhere('supplier_code', 'LIKE', '%' . $searchValue . '%')
                    ->orWhere('contact_person', 'LIKE', '%' . $searchValue . '%')
                    ->orWhere('address', 'LIKE', '%' . $searchValue . '%');
            });
        }

        // Sorting
        $columns = ['id', 'supplier_code', 'name', 'location_id', 'contact_person', 'lead_time_days', 'status', 'remark'];

        if ($request->filled('order')) {
            $orderColumnIndex = $request->input('order.0.column');
            $orderDirection = $request->input('order.0.dir', 'asc');

            if (isset($columns[$orderColumnIndex])) {
                $query->orderBy($columns[$orderColumnIndex], $orderDirection);
            } else {
                $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Get total and filtered counts
        $totalRecords = Supplier::count();
        $filteredRecords = $query->count();

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 15);
        $suppliers = $query->skip($start)->take($length)->get();

        // Format data for DataTables
        $data = [];
        foreach ($suppliers as $index => $supplier) {
            $data[] = [
                'DT_RowIndex' => $start + $index + 1,
                'supplier_code' => $supplier->supplier_code ?? '-',
                'name' => $supplier->name,
                'location' => $supplier->location ? $supplier->location->name : '-',
                'contact_person' => $supplier->contact_person ?? '-',
                'lead_time_days' => $supplier->lead_time_days ? $supplier->lead_time_days . ' days' : '-',
                'status_badge' => $this->getStatusBadge($supplier),
                'remark' => $supplier->remark ?? '-',
                'actions' => $this->getActionButtons($supplier),
                'DT_RowId' => 'row-' . $supplier->id,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    // Helper method untuk status badge
    private function getStatusBadge($supplier)
    {
        $badgeClass = match ($supplier->status) {
            'active' => 'badge bg-success',
            'inactive' => 'badge bg-secondary',
            'blacklisted' => 'badge bg-danger',
            default => 'badge bg-secondary',
        };

        return '<span class="' . $badgeClass . '">' . ucfirst($supplier->status) . '</span>';
    }

    // Helper method untuk action buttons
    private function getActionButtons($supplier)
    {
        $buttons = '<div class="d-flex flex-nowrap gap-1">';

        // Edit button - for authorized users
        if (in_array(auth()->user()->role, ['super_admin', 'admin_procurement', 'admin_logistic', 'admin_finance', 'admin'])) {
            $buttons .=
                '<a href="' .
                route('suppliers.edit', $supplier->id) .
                '" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit">
                <i class="bi bi-pencil-square"></i>
            </a>';
        }

        // Delete button - for authorized users
        if (in_array(auth()->user()->role, ['super_admin', 'admin_procurement', 'admin_logistic', 'admin_finance', 'admin'])) {
            $buttons .=
                '<button type="button" class="btn btn-sm btn-danger btn-delete"
                data-id="' .
                $supplier->id .
                '"
                data-name="' .
                $supplier->name .
                '"
                data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete">
                <i class="bi bi-trash3"></i>
            </button>';
        }

        $buttons .= '</div>';
        return $buttons;
    }

    /**
     * Show the form for creating a new supplier.
     */
    public function create()
    {
        $locations = LocationSupplier::orderBy('name')->get();
        return view('procurement.suppliers.create', compact('locations'));
    }

    /**
     * Store a newly created supplier in storage.
     */
    public function store(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to modify supplier data.');
        }

        $validator = Validator::make($request->all(), [
            'supplier_code' => 'nullable|string|max:50|unique:suppliers,supplier_code',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'location_id' => 'required|exists:location_supplier,id',
            'referral_link' => 'nullable|url|max:255',
            'lead_time_days' => 'required|string|max:10',
            'status' => 'required|in:active,inactive,blacklisted',
            'remark' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            // Add SUP prefix if not present
            $supplierCode = $request->supplier_code;
            if ($supplierCode) {
                $supplierCode = strtoupper($supplierCode);
                if (!str_starts_with($supplierCode, 'SUP')) {
                    $supplierCode = 'SUP' . $supplierCode;
                }
            } else {
                $supplierCode = null;
            }

            Supplier::create([
                'supplier_code' => $supplierCode,
                'name' => $request->name,
                'contact_person' => $request->contact_person,
                'address' => $request->address,
                'location_id' => $request->location_id,
                'referral_link' => $request->referral_link,
                'lead_time_days' => $request->lead_time_days,
                'status' => $request->status,
                'remark' => $request->remark,
            ]);

            return redirect()->route('suppliers.index')->with('success', 'Supplier created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating supplier: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create supplier. Please try again.');
        }
    }

    public function quickStore(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to modify supplier data.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:suppliers,name',
            'location_id' => 'required|exists:location_supplier,id',
            'lead_time_days' => 'required|numeric|min:1',
            'status' => 'in:active', // always active
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $supplier = Supplier::create([
            'supplier_code' => null,
            'name' => $request->name,
            'contact_person' => null,
            'address' => null,
            'location_id' => $request->location_id,
            'referral_link' => null,
            'lead_time_days' => $request->lead_time_days,
            'status' => 'active',
            'remark' => null,
        ]);

        return response()->json(['success' => true, 'supplier' => $supplier]);
    }

    /**
     * Show the form for editing the specified supplier.
     */
    public function edit($id)
    {
        $supplier = Supplier::findOrFail($id);
        $locations = LocationSupplier::orderBy('name')->get();

        return view('procurement.suppliers.edit', compact('supplier', 'locations'));
    }

    /**
     * Update the specified supplier in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to modify supplier data.');
        }

        $validator = Validator::make($request->all(), [
            'supplier_code' => 'nullable|string|max:50|unique:suppliers,supplier_code,' . $supplier->id,
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'location_id' => 'required|exists:location_supplier,id',
            'referral_link' => 'nullable|url|max:255',
            'lead_time_days' => 'required|string|max:10',
            'status' => 'required|in:active,inactive,blacklisted',
            'remark' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            // Add SUP prefix if not present
            $supplierCode = $request->supplier_code;
            if ($supplierCode) {
                $supplierCode = strtoupper($supplierCode);
                if (!str_starts_with($supplierCode, 'SUP')) {
                    $supplierCode = 'SUP' . $supplierCode;
                }
            } else {
                $supplierCode = null;
            }

            $supplier->update([
                'supplier_code' => $supplierCode,
                'name' => $request->name,
                'contact_person' => $request->contact_person,
                'address' => $request->address,
                'location_id' => $request->location_id,
                'referral_link' => $request->referral_link,
                'lead_time_days' => $request->lead_time_days,
                'status' => $request->status,
                'remark' => $request->remark,
            ]);

            return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating supplier: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update supplier. Please try again.');
        }
    }

    /**
     * Remove the specified supplier from storage.
     */
    public function destroy(Request $request, $id)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to modify supplier data.');
        }

        try {
            $supplier = Supplier::findOrFail($id);
            $supplier->delete();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Supplier deleted successfully!',
                ]);
            }

            return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Error deleting supplier: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Failed to delete supplier. Please try again.',
                    ],
                    500,
                );
            }

            return redirect()->back()->with('error', 'Failed to delete supplier. Please try again.');
        }
    }
}
