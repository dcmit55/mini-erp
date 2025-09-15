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
        // Debug: cek data yang diterima
        // dd($request->all());

        $preShippingIds = $request->input('pre_shipping_ids', []);
        if (empty($preShippingIds)) {
            return redirect()->route('pre-shippings.index')->with('error', 'Pilih minimal satu data!');
        }

        $preShippings = PreShipping::with(['externalRequest.project', 'externalRequest.supplier'])
            ->whereIn('id', $preShippingIds)
            ->get();

        if ($preShippings->isEmpty()) {
            return redirect()->route('pre-shippings.index')->with('error', 'Data tidak ditemukan!');
        }

        $freightCompanies = ['DHL', 'FedEx', 'Maersk', 'CMA CGM'];

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

        return redirect()->route('shipping-management.index')->with('success', 'Shipping created!');
    }
}
