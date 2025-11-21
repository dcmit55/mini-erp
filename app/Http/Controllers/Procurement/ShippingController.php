<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Procurement\PreShipping;
use App\Models\Procurement\Shipping;
use App\Models\Procurement\ShippingDetail;
use Illuminate\Support\Facades\Auth;

class ShippingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $allowedRoles = ['super_admin', 'admin_procurement', 'admin_logistic', 'admin'];
        $this->middleware(function ($request, $next) use ($allowedRoles) {
            if (!in_array(auth()->user()->role, $allowedRoles)) {
                abort(403, 'Unauthorized');
            }
            return $next($request);
        });
    }

    public function create(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            return redirect()->route('pre-shippings.index')->with('error', 'You do not have permission to create shipping.');
        }

        $groupKeys = $request->input('group_keys');
        if (is_string($groupKeys)) {
            $groupKeys = json_decode($groupKeys, true);
        }

        if (empty($groupKeys)) {
            return redirect()->route('pre-shippings.index')->with('error', 'Please select at least one group');
        }

        // Cek Domestic Waybill No dan Domestic Cost
        $preShippings = PreShipping::with(['purchaseRequest.project', 'purchaseRequest.supplier', 'purchaseRequest.currency', 'shippingDetail'])
            ->whereIn('group_key', $groupKeys)
            ->get();

        // Validasi domestic_waybill_no dan domestic_cost tidak boleh kosong
        $validPreShippings = $preShippings->filter(function ($item) {
            return $item->purchaseRequest !== null && !empty($item->domestic_waybill_no) && !empty($item->domestic_cost);
        });

        // Jika ada yang kosong, berikan pesan error detail
        $incompleteItems = $preShippings->filter(function ($item) {
            return $item->purchaseRequest !== null && (empty($item->domestic_waybill_no) || empty($item->domestic_cost));
        });

        if (!$incompleteItems->isEmpty()) {
            $incompleteList = $incompleteItems
                ->map(function ($item) {
                    $missing = [];
                    if (empty($item->domestic_waybill_no)) {
                        $missing[] = 'Domestic Waybill No';
                    }
                    if (empty($item->domestic_cost)) {
                        $missing[] = 'Domestic Cost';
                    }

                    return $item->purchaseRequest->material_name . ' (' . implode(', ', $missing) . ' missing)';
                })
                ->implode(', ');

            return redirect()
                ->route('pre-shippings.index')
                ->with('error', 'Cannot proceed to shipping. The following items are incomplete: ' . $incompleteList . '. Please fill in all required fields (Domestic Waybill No & Domestic Cost) before proceeding.');
        }

        if ($validPreShippings->isEmpty()) {
            return redirect()->route('pre-shippings.index')->with('error', 'No valid pre-shipping data found. Some items may have been deleted.');
        }

        // Notifikasi jika ada yang di-filter
        if ($validPreShippings->count() < $preShippings->count()) {
            $skippedCount = $preShippings->count() - $validPreShippings->count();
            session()->flash('warning', "{$skippedCount} pre-shipping item(s) were skipped.");
        }

        $freightCompanies = ['DHL', 'FedEx', 'Maersk', 'CMA CGM'];

        return view('procurement.shippings.create', compact('validPreShippings', 'freightCompanies'))->with('preShippings', $validPreShippings);
    }

    public function store(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            return redirect()->route('pre-shippings.index')->with('error', 'You do not have permission to create shipping.');
        }

        $request->validate([
            'international_waybill_no' => 'required|string|max:255',
            'freight_company' => 'required|string|max:255',
            'freight_price' => 'required|numeric|min:0',
            'eta_to_arrived' => 'required|date',
            'pre_shipping_ids' => 'required|array|min:1',
            'percentage' => 'array',
            'int_cost' => 'array',
            'destination' => 'required|array|min:1',
            'destination.*' => 'required|in:SG,BT,CN,MY,Other',
        ]);

        DB::beginTransaction();
        try {
            // Create shipping record
            $shipping = Shipping::create($request->only(['international_waybill_no', 'freight_company', 'freight_price', 'eta_to_arrived']));

            // Create shipping details dengan destination
            foreach ($request->pre_shipping_ids as $idx => $preShippingId) {
                ShippingDetail::create([
                    'shipping_id' => $shipping->id,
                    'pre_shipping_id' => $preShippingId,
                    'percentage' => $request->percentage[$idx] ?? null,
                    'int_cost' => $request->int_cost[$idx] ?? null,
                    'destination' => $request->destination[$idx],
                ]);
            }

            DB::commit();

            return redirect()->route('shipping-management.index')->with('success', 'Shipping created successfully with destination tracking!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating shipping: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create shipping: ' . $e->getMessage());
        }
    }
}
