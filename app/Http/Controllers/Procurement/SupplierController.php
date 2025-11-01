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
        if (in_array($request->route()->getActionMethod(), $writeMethods) &&
            !in_array($user->role, $allowedRoles)) {
            abort(403, 'You do not have permission to modify supplier data.');
        }
        return $next($request);
    });
}

    /**
     * Display a listing of the suppliers.
     */
    public function index(Request $request)
    {
        $query = Supplier::with('location');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('supplier_code', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Ambil semua data supplier tanpa paginate
        $suppliers = $query->latest()->get();

        $locations = LocationSupplier::orderBy('name')->get();

        return view('procurement.suppliers.index', compact('suppliers', 'locations'));
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
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        // Generate default values for required fields
        $supplier = Supplier::create([
            'supplier_code' => null,
            'name' => $request->name,
            'contact_person' => null,
            'address' => null,
            'location_id' => null,
            'referral_link' => null,
            'lead_time_days' => null,
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
