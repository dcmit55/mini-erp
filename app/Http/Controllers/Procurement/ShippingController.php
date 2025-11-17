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

        // Eager load dengan lebih teliti
        $preShippings = PreShipping::with(['purchaseRequest.project', 'purchaseRequest.supplier', 'purchaseRequest.currency'])
            ->whereIn('group_key', $groupKeys)
            ->get();

        // Filter out yang purchaseRequestnya null
        $validPreShippings = $preShippings->filter(function ($item) {
            return $item->purchaseRequest !== null;
        });

        if ($validPreShippings->isEmpty()) {
            return redirect()->route('pre-shippings.index')->with('error', 'No valid pre-shipping data found. Some items may have been deleted.');
        }

        // Notifikasi jika ada yang di-filter
        if ($validPreShippings->count() < $preShippings->count()) {
            $skippedCount = $preShippings->count() - $validPreShippings->count();
            session()->flash('warning', "{$skippedCount} pre-shipping item(s) were skipped because their purchase request no longer exists.");
        }

        $freightCompanies = ['DHL', 'FedEx', 'Maersk', 'CMA CGM'];

        // Pass validPreShippings saja
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
        ]);

        $shipping = Shipping::create($request->only(['international_waybill_no', 'freight_company', 'freight_price', 'eta_to_arrived']));

        foreach ($request->pre_shipping_ids as $idx => $preShippingId) {
            ShippingDetail::create([
                'shipping_id' => $shipping->id,
                'pre_shipping_id' => $preShippingId,
                'percentage' => $request->percentage[$idx] ?? null,
                'int_cost' => $request->int_cost[$idx] ?? null,
            ]);
        }

        return redirect()->route('shipping-management.index')->with('success', 'Shipping created!');
    }
}
