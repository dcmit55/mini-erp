<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Procurement\Shipping;
use App\Models\Procurement\GoodsReceive;
use App\Models\Procurement\GoodsReceiveDetail;
use Illuminate\Support\Facades\Auth;

class GoodsReceiveController extends Controller
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

    public function store(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'You do not have permission to create goods receive.',
                ],
                403,
            );
        }

        $request->validate([
            'shipping_id' => 'required|exists:shippings,id',
            'arrived_date' => 'required|date',
            'received_qty' => 'required|array',
            'received_qty.*' => 'nullable|string|max:255',
        ]);

        $shipping = Shipping::with(['details.preShipping.purchaseRequest.project', 'details.preShipping.purchaseRequest.supplier'])->findOrFail($request->shipping_id);

        $goodsReceive = GoodsReceive::create([
            'shipping_id' => $shipping->id,
            'international_waybill_no' => $shipping->international_waybill_no,
            'freight_company' => $shipping->freight_company,
            'freight_price' => $shipping->freight_price,
            'arrived_date' => $request->arrived_date,
        ]);

        foreach ($shipping->details as $idx => $detail) {
            // Gunakan qty_to_buy bukan required_quantity
            $purchasedQty = $detail->preShipping->purchaseRequest->qty_to_buy ?? $detail->preShipping->purchaseRequest->required_quantity;

            GoodsReceiveDetail::create([
                'goods_receive_id' => $goodsReceive->id,
                'shipping_detail_id' => $detail->id,
                'purchase_type' => $detail->preShipping->purchaseRequest->type,
                'project_name' => $detail->preShipping->purchaseRequest->project->name ?? '-',
                'material_name' => $detail->preShipping->purchaseRequest->material_name,
                'supplier_name' => $detail->preShipping->purchaseRequest->supplier->name ?? '-',
                'unit_price' => $detail->preShipping->purchaseRequest->price_per_unit,
                'domestic_waybill_no' => $detail->preShipping->domestic_waybill_no,
                'purchased_qty' => $purchasedQty,
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
        return view('procurement.goods_receive_listing.index', compact('goodsReceives'));
    }
}
