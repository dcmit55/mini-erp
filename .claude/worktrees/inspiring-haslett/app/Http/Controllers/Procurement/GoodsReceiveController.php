<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Procurement\Shipping;
use App\Models\Procurement\GoodsReceive;
use App\Models\Procurement\GoodsReceiveDetail;
use App\Models\Procurement\ShortageItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    public function index()
    {
        $goodsReceives = GoodsReceive::with(['details'])
            ->orderByDesc('arrived_date')
            ->get();
        return view('procurement.goods_receive_listing.index', compact('goodsReceives'));
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

        // Validasi
        $request->validate([
            'shipping_id' => 'required|exists:shippings,id',
            'arrived_date' => 'required|date',
            'received_qty' => 'required|array',
            'received_qty.*' => 'required|string|max:255',
            'destination' => 'required|array',
            'destination.*' => 'required|in:SG,BT,CN,MY,Other',
        ]);

        $shipping = Shipping::with(['details.preShipping.purchaseRequest.project', 'details.preShipping.purchaseRequest.supplier', 'details.preShipping.purchaseRequest.inventory'])->findOrFail($request->shipping_id);

        DB::beginTransaction();
        try {
            // Create Goods Receive
            $goodsReceive = GoodsReceive::create([
                'shipping_id' => $shipping->id,
                'international_waybill_no' => $shipping->international_waybill_no,
                'freight_company' => $shipping->freight_company,
                'freight_price' => $shipping->freight_price,
                'arrived_date' => $request->arrived_date,
            ]);

            // Counter untuk shortage items
            $shortageCount = 0;

            foreach ($shipping->details as $idx => $detail) {
                // Get Purchase Request dari PreShipping ATAU ShortageItem
                $purchaseRequest = $detail->getSourcePurchaseRequest();

                if (!$purchaseRequest) {
                    \Log::error('Shipping detail has no source PR', [
                        'detail_id' => $detail->id,
                        'pre_shipping_id' => $detail->pre_shipping_id,
                        'shortage_item_id' => $detail->shortage_item_id,
                    ]);
                    continue;
                }

                $purchasedQty = $detail->isShortageResend() ? $detail->shortageItem->shortage_qty : $purchaseRequest->qty_to_buy ?? $purchaseRequest->required_quantity;

                $receivedQtyRaw = $request->received_qty[$idx] ?? '0';
                $receivedQty = (float) str_replace(',', '.', trim($receivedQtyRaw));
                $finalDestination = $request->destination[$idx];

                // Create Goods Receive Detail
                $goodsReceiveDetail = GoodsReceiveDetail::create([
                    'goods_receive_id' => $goodsReceive->id,
                    'shipping_detail_id' => $detail->id,
                    'purchase_type' => $purchaseRequest->type,
                    'project_name' => $purchaseRequest->project->name ?? '-',
                    'material_name' => $purchaseRequest->material_name,
                    'supplier_name' => $purchaseRequest->supplier->name ?? '-',
                    'unit_price' => $purchaseRequest->price_per_unit,
                    'domestic_waybill_no' => $detail->isShortageResend() ? $detail->shortageItem->old_domestic_wbl : $detail->preShipping->domestic_waybill_no ?? '-',
                    'purchased_qty' => $purchasedQty,
                    'received_qty' => $receivedQtyRaw,
                    'destination' => $finalDestination,
                    'extra_cost' => $detail->extra_cost ?? 0,
                    'extra_cost_reason' => $detail->extra_cost_reason,
                ]);

                // Handle shortage items: Update after receiving
                if ($detail->isShortageResend()) {
                    $shortage = $detail->shortageItem;

                    if ($receivedQty >= $shortage->shortage_qty) {
                        // Fully resolved
                        $shortage->update([
                            'status' => 'fully_reshipped',
                            'notes' => ($shortage->notes ? $shortage->notes . "\n" : '') . 'Fully received on ' . now()->format('Y-m-d H:i:s') . " | Received: {$receivedQty}",
                        ]);

                        \Log::info('Shortage fully resolved', [
                            'shortage_id' => $shortage->id,
                            'original_shortage' => $shortage->shortage_qty,
                            'received' => $receivedQty,
                        ]);
                    } else {
                        // Partially resolved - create new shortage for remaining
                        $remainingShortage = $shortage->shortage_qty - $receivedQty;

                        $shortage->update([
                            'status' => 'partially_reshipped',
                            'notes' => ($shortage->notes ? $shortage->notes . "\n" : '') . 'Partially received on ' . now()->format('Y-m-d H:i:s') . " | Received: {$receivedQty} / {$shortage->shortage_qty}",
                        ]);

                        // Create new shortage item for remaining qty
                        ShortageItem::create([
                            'goods_receive_detail_id' => $goodsReceiveDetail->id,
                            'purchase_request_id' => $purchaseRequest->id,
                            'material_name' => $purchaseRequest->material_name,
                            'purchased_qty' => $shortage->shortage_qty,
                            'received_qty' => $receivedQty,
                            'shortage_qty' => $remainingShortage,
                            'status' => 'pending',
                            'resend_count' => $shortage->resend_count + 1,
                            'old_domestic_wbl' => null,
                            'notes' => "Remaining shortage from previous resend #{$shortage->resend_count}",
                        ]);

                        \Log::warning('Partial shortage after resend', [
                            'original_shortage_id' => $shortage->id,
                            'original_qty' => $shortage->shortage_qty,
                            'received' => $receivedQty,
                            'remaining_shortage' => $remainingShortage,
                        ]);
                    }
                } else {
                    // Normal item shortage detection (existing logic)
                    if ($receivedQty < $purchasedQty) {
                        $shortageQty = $purchasedQty - $receivedQty;

                        ShortageItem::create([
                            'goods_receive_detail_id' => $goodsReceiveDetail->id,
                            'purchase_request_id' => $purchaseRequest->id,
                            'material_name' => $purchaseRequest->material_name,
                            'purchased_qty' => $purchasedQty,
                            'received_qty' => $receivedQty,
                            'shortage_qty' => $shortageQty,
                            'status' => 'pending',
                            'resend_count' => 0,
                            'old_domestic_wbl' => $detail->preShipping->domestic_waybill_no,
                            'notes' => 'Auto-detected shortage on ' . now()->format('Y-m-d H:i:s'),
                        ]);

                        $shortageCount++;
                    }
                }
            }

            DB::commit();

            // Return response dengan shortage info
            $responseMessage = 'Goods Receive created successfully!';
            if ($shortageCount > 0) {
                $responseMessage .= " | {$shortageCount} shortage item(s) detected and logged.";
            }

            return response()->json([
                'success' => true,
                'message' => $responseMessage,
                'shortage_count' => $shortageCount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating goods receive: ' . $e->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to create goods receive: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }
}
