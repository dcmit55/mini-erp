<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PreShipping; // <-- Tambahkan ini!
use App\Models\Shipping;
use App\Models\ShippingDetail;

class ShippingController extends Controller
{
    public function create(Request $request)
    {
        $preShippingIds = $request->input('pre_shipping_ids', []);
        $preShippings = PreShipping::with(['externalRequest.project', 'externalRequest.supplier'])
            ->whereIn('id', $preShippingIds)
            ->get();

        $freightCompanies = ['DHL', 'FedEx', 'Maersk', 'CMA CGM']; // Dummy

        return view('shippings.create', compact('preShippings', 'freightCompanies'));
    }

    public function store(Request $request)
    {
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

        return redirect()->route('shippings.create')->with('success', 'Shipping created!');
    }
}
