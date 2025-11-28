<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Procurement\ShortageItem;
use App\Models\Procurement\PurchaseRequest;
use App\Models\Procurement\PreShipping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ShortageItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $allowedRoles = ['super_admin', 'admin_procurement', 'admin_logistic', 'admin'];
        $this->middleware(function ($request, $next) use ($allowedRoles) {
            if (!in_array(auth()->user()->role, $allowedRoles)) {
                abort(403, 'Unauthorized to manage shortage items');
            }
            return $next($request);
        });
    }

    /**
     * Display list of shortage items (for Pre-Shipping Index view)
     */
    public function index(Request $request)
    {
        // Filter untuk shortage items yang resolvable (pending atau partially reshipped)
        $shortageItems = ShortageItem::with(['purchaseRequest.project', 'purchaseRequest.supplier', 'goodsReceiveDetail'])
            ->resolvable() // Scope: status = pending atau partially_reshipped
            ->recent()
            ->get();

        // Group by supplier untuk better UI
        $groupedShortages = $shortageItems->groupBy(function ($item) {
            return $item->purchaseRequest->supplier_id ?? 'no_supplier';
        });

        if ($request->ajax()) {
            return response()->json([
                'shortage_items' => $shortageItems,
                'grouped_shortages' => $groupedShortages,
            ]);
        }

        return view('procurement.shortage_items.index', compact('shortageItems', 'groupedShortages'));
    }

    /**
     * BULK RESEND SHORTAGE ITEMS - Core Logic
     */
    public function bulkResend(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'You do not have permission to resend shortage items.',
                ],
                403,
            );
        }

        $request->validate([
            'shortage_item_ids' => 'required|array|min:1',
            'shortage_item_ids.*' => 'required|exists:shortage_items,id',
            'old_domestic_wbl' => 'nullable|array',
            'old_domestic_wbl.*' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $shortageIds = $request->shortage_item_ids;
            $oldWaybills = $request->old_domestic_wbl ?? [];

            $createdPRIds = [];
            $createdPreShippingIds = [];
            $updatedShortageCount = 0;

            foreach ($shortageIds as $index => $shortageId) {
                $shortage = ShortageItem::with(['purchaseRequest', 'goodsReceiveDetail'])->findOrFail($shortageId);

                if (!$shortage->isResolvable()) {
                    \Log::warning('Attempted to resend non-resolvable shortage', [
                        'shortage_id' => $shortageId,
                        'status' => $shortage->status,
                    ]);
                    continue;
                }

                $originalPR = $shortage->purchaseRequest;

                // ⭐ FIX: Ensure supplier_id exists
                $supplierId = $originalPR->supplier_id;
                if (!$supplierId || !$originalPR->supplier) {
                    // Fallback: Get supplier dari goods_receive_detail
                    $goodsReceiveDetail = $shortage->goodsReceiveDetail;
                    if ($goodsReceiveDetail && $goodsReceiveDetail->supplier_name) {
                        // Try to find supplier by name
                        $fallbackSupplier = \App\Models\Procurement\Supplier::where('name', $goodsReceiveDetail->supplier_name)->first();
                        $supplierId = $fallbackSupplier ? $fallbackSupplier->id : null;
                    }

                    // Last resort: Use first active supplier
                    if (!$supplierId) {
                        $fallbackSupplier = \App\Models\Procurement\Supplier::active()->first();
                        $supplierId = $fallbackSupplier ? $fallbackSupplier->id : null;

                        \Log::warning('Shortage Resend - No supplier found, using fallback', [
                            'shortage_id' => $shortageId,
                            'fallback_supplier_id' => $supplierId,
                        ]);
                    }
                }

                // ⭐ FIX: Ensure delivery_date exists
                $deliveryDate = $originalPR->delivery_date;
                if (!$deliveryDate) {
                    // Fallback: Use today + 7 days
                    $deliveryDate = now()->addDays(7)->format('Y-m-d');

                    \Log::warning('Shortage Resend - No delivery_date, using fallback', [
                        'shortage_id' => $shortageId,
                        'fallback_delivery_date' => $deliveryDate,
                    ]);
                }

                // ⚠️ Skip jika masih tidak ada supplier_id setelah fallback
                if (!$supplierId) {
                    \Log::error('Shortage Resend - Cannot create PR without supplier_id', [
                        'shortage_id' => $shortageId,
                        'original_pr_id' => $originalPR->id,
                    ]);
                    continue;
                }

                // CREATE NEW PURCHASE REQUEST dengan validated data
                $newPR = PurchaseRequest::create([
                    'type' => $originalPR->type,
                    'material_name' => $shortage->material_name,
                    'inventory_id' => $originalPR->inventory_id,
                    'required_quantity' => $shortage->shortage_qty,
                    'qty_to_buy' => $shortage->shortage_qty,
                    'unit' => $originalPR->unit,
                    'stock_level' => $originalPR->stock_level,
                    'project_id' => $originalPR->project_id,
                    'requested_by' => Auth::id(),
                    'supplier_id' => $supplierId, // ✅ Validated supplier_id
                    'price_per_unit' => $originalPR->price_per_unit,
                    'currency_id' => $originalPR->currency_id,
                    'approval_status' => 'Approved',
                    'delivery_date' => $deliveryDate, // ✅ Validated delivery_date
                    'remark' => "🔄 Resend shortage from PR#{$originalPR->id} | Shortage: {$shortage->shortage_qty}",
                ]);

                $createdPRIds[] = $newPR->id;

                // ⭐ DETAILED LOGGING dengan validated data
                \Log::info('Shortage Resend - PR Created with validated data', [
                    'shortage_id' => $shortageId,
                    'new_pr_id' => $newPR->id,
                    'material_name' => $newPR->material_name,
                    'qty' => $newPR->qty_to_buy,
                    'supplier_id' => $newPR->supplier_id,
                    'delivery_date' => $newPR->delivery_date,
                    'approval_status' => $newPR->approval_status,
                    'is_fallback_supplier' => $originalPR->supplier_id ? false : true,
                    'is_fallback_delivery_date' => $originalPR->delivery_date ? false : true,
                ]);

                // CREATE PRE-SHIPPING (sama seperti sebelumnya)
                $groupKey = PreShipping::generateGroupKey($newPR->supplier_id, $newPR->delivery_date);

                $preShipping = PreShipping::create([
                    'purchase_request_id' => $newPR->id,
                    'group_key' => $groupKey,
                    'cost_allocation_method' => 'value',
                    'domestic_waybill_no' => $oldWaybills[$index] ?? null,
                ]);

                $createdPreShippingIds[] = $preShipping->id;

                // ⭐ DETAILED LOGGING
                \Log::info('Shortage Resend - PreShipping Created', [
                    'pre_shipping_id' => $preShipping->id,
                    'group_key' => $groupKey,
                    'purchase_request_id' => $newPR->id,
                ]);

                // UPDATE SHORTAGE ITEM
                $shortage->update([
                    'status' => 'reshipped',
                    'resend_count' => $shortage->resend_count + 1,
                    'notes' => ($shortage->notes ? $shortage->notes . "\n" : '') . 'Reshipped on ' . now()->format('Y-m-d H:i:s') . " | New PR#{$newPR->id}",
                ]);

                $updatedShortageCount++;

                // ⭐ DETAILED LOGGING
                \Log::info('Shortage Resend - Status Updated', [
                    'shortage_id' => $shortageId,
                    'new_status' => 'reshipped',
                    'resend_count' => $shortage->resend_count,
                ]);
            }

            DB::commit();

            // ⭐ SUMMARY LOGGING
            \Log::info('Bulk Resend Complete', [
                'total_processed' => $updatedShortageCount,
                'created_pr_ids' => $createdPRIds,
                'created_pre_shipping_ids' => $createdPreShippingIds,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "✅ {$updatedShortageCount} shortage item(s) successfully resent! Check Pre-Shipping tab.",
                'created_purchase_requests' => $createdPRIds,
                'created_pre_shippings' => $createdPreShippingIds,
                'updated_shortage_count' => $updatedShortageCount,
                'redirect_url' => route('pre-shippings.index') . '?success=' . urlencode('New items added to Pre-Shipping') . '&highlight_groups=new',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Bulk Resend Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Cancel shortage item (user decides not to resend)
     */
    public function cancel(Request $request, $id)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'You do not have permission to cancel shortage items.',
                ],
                403,
            );
        }

        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $shortage = ShortageItem::findOrFail($id);

            if (!$shortage->isResolvable()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'This shortage item cannot be canceled (already processed).',
                    ],
                    400,
                );
            }

            $shortage->cancel($request->reason);

            \Log::info('Shortage item canceled', [
                'shortage_id' => $id,
                'reason' => $request->reason,
                'canceled_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Shortage item canceled successfully.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error canceling shortage item', [
                'shortage_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to cancel shortage item.',
                ],
                500,
            );
        }
    }

    /**
     * Show details of specific shortage item
     */
    public function show($id)
    {
        $shortage = ShortageItem::with(['purchaseRequest.project', 'purchaseRequest.supplier', 'purchaseRequest.user', 'goodsReceiveDetail.goodsReceive'])->findOrFail($id);

        return view('procurement.shortage_items.show', compact('shortage'));
    }

    /**
     * Get shortage items by status (for filtering in Pre-Shipping Index)
     */
    public function getByStatus(Request $request)
    {
        $status = $request->query('status', 'pending');

        $query = ShortageItem::with(['purchaseRequest.project', 'purchaseRequest.supplier', 'goodsReceiveDetail'])->recent();

        // ⭐ FIX: Support 'all' status
        if ($status === 'all') {
            // No filter - return all statuses
        } elseif ($status === 'resolvable') {
            $query->resolvable();
        } else {
            $query->where('status', $status);
        }

        $shortageItems = $query->get();

        return response()->json([
            'shortage_items' => $shortageItems,
            'total_count' => $shortageItems->count(),
        ]);
    }
}
