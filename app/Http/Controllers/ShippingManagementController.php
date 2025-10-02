<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shipping;

class ShippingManagementController extends Controller
{
    public function index()
    {
        $shippings = Shipping::with(['details.preShipping.purchaseRequest.project', 'details.preShipping.purchaseRequest.supplier'])
            ->orderByDesc('created_at')
            ->get();

        return view('shipping_management.index', compact('shippings'));
    }
    public function detail($id)
    {
        $shipping = Shipping::with(['details.preShipping.purchaseRequest.project', 'details.preShipping.purchaseRequest.supplier'])->findOrFail($id);

        $details = [];
        foreach ($shipping->details as $detail) {
            $details[] = [
                'purchase_type' => ucfirst(str_replace('_', ' ', $detail->preShipping->purchaseRequest->type)),
                'project_name' => $detail->preShipping->purchaseRequest->project->name ?? '-',
                'material_name' => $detail->preShipping->purchaseRequest->material_name,
                'supplier_name' => $detail->preShipping->purchaseRequest->supplier->name ?? '-',
                'unit_price' => $detail->preShipping->purchaseRequest->price_per_unit,
                'domestic_waybill_no' => $detail->preShipping->domestic_waybill_no,
                'purchased_qty' => $detail->preShipping->purchaseRequest->required_quantity,
            ];
        }

        return response()->json([
            'shipping' => $shipping,
            'details' => $details,
        ]);
    }
}
