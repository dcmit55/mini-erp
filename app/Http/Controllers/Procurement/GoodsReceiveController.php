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
                $purchasedQty = $detail->preShipping->purchaseRequest->qty_to_buy ?? $detail->preShipping->purchaseRequest->required_quantity;
                $purchaseRequest = $detail->preShipping->purchaseRequest;

                // Parse received_qty (bisa string dengan koma)
                $receivedQtyRaw = $request->received_qty[$idx] ?? '0';
                $receivedQty = (float) str_replace(',', '.', trim($receivedQtyRaw));

                $finalDestination = $request->destination[$idx];
                $originalDestination = $detail->destination;

                // Create Goods Receive Detail
                $goodsReceiveDetail = GoodsReceiveDetail::create([
                    'goods_receive_id' => $goodsReceive->id,
                    'shipping_detail_id' => $detail->id,
                    'purchase_type' => $purchaseRequest->type,
                    'project_name' => $purchaseRequest->project->name ?? '-',
                    'material_name' => $purchaseRequest->material_name,
                    'supplier_name' => $purchaseRequest->supplier->name ?? '-',
                    'unit_price' => $purchaseRequest->price_per_unit,
                    'domestic_waybill_no' => $detail->preShipping->domestic_waybill_no,
                    'purchased_qty' => $purchasedQty,
                    'received_qty' => $receivedQtyRaw, // Save as string (original behavior)
                    'destination' => $finalDestination,
                    'extra_cost' => $detail->extra_cost ?? 0,
                    'extra_cost_reason' => $detail->extra_cost_reason,
                ]);

                // SHORTAGE DETECTION LOGIC
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

                    \Log::warning('Shortage detected', [
                        'goods_receive_id' => $goodsReceive->id,
                        'material_name' => $purchaseRequest->material_name,
                        'purchased_qty' => $purchasedQty,
                        'received_qty' => $receivedQty,
                        'shortage_qty' => $shortageQty,
                        'detected_by' => Auth::id(),
                    ]);
                }

                // Log destination changes
                if ($finalDestination !== $originalDestination) {
                    \Log::info('Destination changed during Goods Receive', [
                        'goods_receive_id' => $goodsReceive->id,
                        'material_name' => $purchaseRequest->material_name,
                        'original_destination' => $originalDestination,
                        'final_destination' => $finalDestination,
                        'changed_by' => Auth::id(),
                        'reason' => 'Changed during goods receive process',
                    ]);
                }

                // Update inventory supplier jika supplier berubah
                if ($purchaseRequest->type === 'restock' && $purchaseRequest->hasSupplierChanged()) {
                    $inventory = $purchaseRequest->inventory;

                    if ($inventory) {
                        $oldSupplierId = $inventory->supplier_id;
                        $newSupplierId = $purchaseRequest->supplier_id;

                        $inventory->update([
                            'supplier_id' => $newSupplierId,
                        ]);

                        \Log::info('Inventory supplier updated after Goods Receive', [
                            'inventory_id' => $inventory->id,
                            'inventory_name' => $inventory->name,
                            'old_supplier_id' => $oldSupplierId,
                            'new_supplier_id' => $newSupplierId,
                            'purchase_request_id' => $purchaseRequest->id,
                            'goods_receive_id' => $goodsReceive->id,
                            'final_destination' => $finalDestination,
                            'reason' => $purchaseRequest->supplier_change_reason ?? 'Updated via Goods Receive',
                            'updated_by' => Auth::id(),
                        ]);
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
