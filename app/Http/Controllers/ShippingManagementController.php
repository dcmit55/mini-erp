<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shipping;

class ShippingManagementController extends Controller
{
    public function index()
    {
        $shippings = Shipping::with(['details.preShipping.externalRequest.project', 'details.preShipping.externalRequest.supplier'])
            ->orderByDesc('created_at')
            ->get();

        return view('shipping_management.index', compact('shippings'));
    }
    public function detail($id)
    {
        $shipping = Shipping::with(['details.preShipping.externalRequest.project', 'details.preShipping.externalRequest.supplier'])->findOrFail($id);

        $details = [];
        foreach ($shipping->details as $detail) {
            $details[] = [
                'purchase_type' => ucfirst(str_replace('_', ' ', $detail->preShipping->externalRequest->type)),
                'project_name' => $detail->preShipping->externalRequest->project->name ?? '-',
                'material_name' => $detail->preShipping->externalRequest->material_name,
                'supplier_name' => $detail->preShipping->externalRequest->supplier->name ?? '-',
                'unit_price' => $detail->preShipping->externalRequest->price_per_unit,
                'domestic_waybill_no' => $detail->preShipping->domestic_waybill_no,
                'purchased_qty' => $detail->preShipping->externalRequest->required_quantity,
            ];
        }

        return response()->json([
            'shipping' => $shipping,
            'details' => $details,
        ]);
    }
}
