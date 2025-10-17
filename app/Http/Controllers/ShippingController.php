<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PreShipping; // <-- Tambahkan ini!
use App\Models\Shipping;
use App\Models\ShippingDetail;

class ShippingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            return redirect()->route('pre-shippings.index')->with('error', 'You do not have permission to create shipping.');
        }

        // Decode group keys dari JSON
        $groupKeys = $request->input('group_keys');
        if (is_string($groupKeys)) {
            $groupKeys = json_decode($groupKeys, true);
        }

        if (empty($groupKeys)) {
            return redirect()->route('pre-shippings.index')->with('error', 'Please select at least one group');
        }

        $preShippings = PreShipping::with(['purchaseRequest.project', 'purchaseRequest.supplier'])
            ->whereIn('group_key', $groupKeys)
            ->get();

        if ($preShippings->isEmpty()) {
            return redirect()->route('pre-shippings.index')->with('error', 'No data found for selected groups');
        }

        $freightCompanies = ['DHL', 'FedEx', 'Maersk', 'CMA CGM'];

        return view('shippings.create', compact('preShippings', 'freightCompanies'));
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
