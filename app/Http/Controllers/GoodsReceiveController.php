<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shipping;
use App\Models\GoodsReceive;
use App\Models\GoodsReceiveDetail;

class GoodsReceiveController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'shipping_id' => 'required|exists:shippings,id',
            'arrived_date' => 'required|date',
            'received_qty' => 'required|array',
            'received_qty.*' => 'nullable|string|max:255',
        ]);

        $shipping = Shipping::with(['details.preShipping.externalRequest.project', 'details.preShipping.externalRequest.supplier'])->findOrFail($request->shipping_id);

        $goodsReceive = GoodsReceive::create([
            'shipping_id' => $shipping->id,
            'international_waybill_no' => $shipping->international_waybill_no,
            'freight_company' => $shipping->freight_company,
            'freight_price' => $shipping->freight_price,
            'arrived_date' => $request->arrived_date,
        ]);

        foreach ($shipping->details as $idx => $detail) {
            GoodsReceiveDetail::create([
                'goods_receive_id' => $goodsReceive->id,
                'shipping_detail_id' => $detail->id,
                'purchase_type' => $detail->preShipping->externalRequest->type,
                'project_name' => $detail->preShipping->externalRequest->project->name ?? '-',
                'material_name' => $detail->preShipping->externalRequest->material_name,
                'supplier_name' => $detail->preShipping->externalRequest->supplier->name ?? '-',
                'unit_price' => $detail->preShipping->externalRequest->price_per_unit,
                'domestic_waybill_no' => $detail->preShipping->domestic_waybill_no,
                'purchased_qty' => $detail->preShipping->externalRequest->required_quantity,
                'received_qty' => $request->received_qty[$idx] ?? null,
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function index()
    {
        $goodsReceives = GoodsReceive::with(['details'])
            ->orderByDesc('arrived_date')
            ->get();
        return view('goods_receive_listing.index', compact('goodsReceives'));
    }
}
