<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Procurement\PreShipping;
use App\Models\Procurement\Shipping;
use App\Models\Procurement\ShippingDetail;
use App\Models\Procurement\ShortageItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShippingController extends Controller
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

    public function create(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            return redirect()->route('pre-shippings.index')->with('error', 'You do not have permission to create shipping.');
        }

        // Accept both group_keys AND shortage_item_ids
        $groupKeys = $request->input('group_keys');
        if (is_string($groupKeys)) {
            $groupKeys = json_decode($groupKeys, true);
        }
        $groupKeys = $groupKeys ?: [];

        $shortageItemIds = $request->input('shortage_item_ids');
        if (is_string($shortageItemIds)) {
            $shortageItemIds = json_decode($shortageItemIds, true);
        }
        $shortageItemIds = $shortageItemIds ?: [];

        // Validasi: At least one selection required
        if (empty($groupKeys) && empty($shortageItemIds)) {
            return redirect()->route('pre-shippings.index')->with('error', 'Please select at least one group or shortage item');
        }

        // STEP 1: Load Normal Pre-Shipping Items
        $normalPreShippings = collect();
        if (!empty($groupKeys)) {
            $normalPreShippings = PreShipping::with(['purchaseRequest.project', 'purchaseRequest.supplier', 'purchaseRequest.currency', 'shippingDetail'])
                ->whereIn('group_key', $groupKeys)
                ->get();

            // Filter yang valid
            $normalPreShippings = $normalPreShippings->filter(function ($item) {
                return $item->purchaseRequest !== null && !empty($item->domestic_waybill_no) && !empty($item->domestic_cost);
            });
        }

        // STEP 2: Load Shortage Items LANGSUNG (TANPA Create PR Baru)
        $shortageItems = collect();
        if (!empty($shortageItemIds)) {
            $shortageItems = ShortageItem::with(['purchaseRequest.project', 'purchaseRequest.supplier', 'purchaseRequest.currency'])
                ->whereIn('id', $shortageItemIds)
                ->resolvable() // Only pending atau partially_reshipped
                ->get();

            // ⭐ PERBAIKAN: Filter HANYA berdasarkan status shortage & keberadaan di shipping_detail
            $shortageItems = $shortageItems->filter(function ($shortage) {
                // 1️⃣ Cek apakah shortage ini sudah pernah di-ship sebelumnya
                // (NOTE: Bisa di-ship ulang berkali-kali, tapi tidak boleh diadd ke 2 shipping sekaligus)
                $currentShippingDetail = \App\Models\Procurement\ShippingDetail::where('shortage_item_id', $shortage->id)
                    ->whereHas('shipping', function ($q) {
                        // Hanya block jika ada shipping yang BELUM di-terima
                        $q->doesntHave('goodsReceive');
                    })
                    ->exists();

                if ($currentShippingDetail) {
                    \Log::warning('Shortage item SKIPPED - Already in pending shipment', [
                        'shortage_id' => $shortage->id,
                        'status' => $shortage->status,
                        'reason' => 'Cannot add to multiple shipments simultaneously',
                    ]);
                    return false;
                }

                // 2️⃣ Ensure PR exists (TIDAK PERLU check apakah PR sudah shipped)
                // Karena shortage = resend dari PR yang sudah shipped
                if (!$shortage->purchaseRequest) {
                    \Log::warning('Shortage item SKIPPED - No original PR', [
                        'shortage_id' => $shortage->id,
                    ]);
                    return false;
                }

                // ✅ Item ini VALID untuk di-ship (TERLEPAS dari status PR)
                return true;
            });

            \Log::info('Shortage Items Filtered', [
                'requested' => count($shortageItemIds),
                'valid_for_shipping' => $shortageItems->count(),
                'skipped' => count($shortageItemIds) - $shortageItems->count(),
            ]);
        }

        // STEP 3: Merge Collections
        $allItems = collect();

        // Add normal items dengan flag
        foreach ($normalPreShippings as $preShipping) {
            $preShipping->is_shortage = false;
            $preShipping->item_type = 'normal';
            $allItems->push($preShipping);
        }

        // Add shortage items dengan flag
        foreach ($shortageItems as $shortage) {
            // Create wrapper object mirip PreShipping untuk consistency di view
            $wrapper = (object) [
                'id' => 'SHORTAGE_' . $shortage->id, // Unique ID untuk form
                'shortage_item_id' => $shortage->id,
                'purchase_request_id' => $shortage->purchase_request_id,
                'purchaseRequest' => $shortage->purchaseRequest,
                'domestic_waybill_no' => $shortage->old_domestic_wbl,
                'domestic_cost' => 0, // Shortage tidak punya domestic cost
                'allocated_cost' => 0,
                'is_shortage' => true,
                'item_type' => 'shortage',
                'shortage_qty' => $shortage->shortage_qty,
                'resend_count' => $shortage->resend_count,
            ];

            $allItems->push($wrapper);
        }

        if ($allItems->isEmpty()) {
            return redirect()->route('pre-shippings.index')->with('error', 'No valid items found.');
        }

        // Summary message
        $normalCount = $normalPreShippings->count();
        $shortageCount = $shortageItems->count();
        $summaryMessage = "Creating shipping with {$normalCount} normal item(s)";
        if ($shortageCount > 0) {
            $summaryMessage .= " and {$shortageCount} shortage resend item(s)";
        }
        session()->flash('info', $summaryMessage);

        $freightCompanies = ['DHL', 'FedEx', 'Maersk', 'CMA CGM'];

        return view('procurement.shippings.create', compact('allItems', 'freightCompanies'))->with('preShippings', $allItems); // Backward compatibility
    }

    public function store(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            return redirect()->route('pre-shippings.index')->with('error', 'You do not have permission to create shipping.');
        }

        \Log::info('🚀 ShippingController.store() called', [
            'waybill' => $request->international_waybill_no,
            'items_count' => count($request->items ?? []),
            'timestamp' => now()->toDateTimeString(),
        ]);

        $request->validate([
            'international_waybill_no' => 'required|string|max:255',
            'freight_company' => 'required|string|max:255',
            'freight_method' => 'required|in:Sea Freight,Air Freight',
            'freight_price' => 'required|numeric|min:0',
            'eta_to_arrived' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|string',
            'items.*.item_type' => 'required|in:normal,shortage',
            'int_allocation_method' => 'required|in:quantity,percentage,value',
            'percentage' => 'nullable|array',
            'percentage.*' => 'nullable|numeric|min:0|max:100',
            'int_cost' => 'required|array',
            'int_cost.*' => 'required|numeric|min:0',
            'extra_cost' => 'nullable|array',
            'extra_cost.*' => 'nullable|numeric|min:0',
            'extra_cost_reason' => 'nullable|array',
            'extra_cost_reason.*' => 'nullable|string|max:255',
            'destination' => 'required|array|min:1',
            'destination.*' => 'required|in:SG,BT,CN,MY,Other',
        ]);

        \Log::info('✅ Validation passed for waybill: ' . $request->international_waybill_no);

        // Validasi percentage total
        if ($request->int_allocation_method === 'percentage') {
            $totalPercentage = array_sum($request->percentage ?? []);
            if (abs($totalPercentage - 100) > 0.5) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['percentage' => "Total percentage must be 100%. Current: {$totalPercentage}%"]);
            }
        }

        // ⭐ START TRANSACTION IMMEDIATELY - BEFORE ANY CREATE
        DB::beginTransaction();
        try {
            // Create shipping
            $shipping = Shipping::create([
                'international_waybill_no' => $request->international_waybill_no,
                'freight_company' => $request->freight_company,
                'freight_method' => $request->freight_method,
                'freight_price' => $request->freight_price,
                'eta_to_arrived' => $request->eta_to_arrived,
            ]);

            \Log::info('✅ Shipping record created', [
                'shipping_id' => $shipping->id,
                'waybill' => $shipping->international_waybill_no,
            ]);

            $normalItemCount = 0;
            $shortageItemCount = 0;

            // Process Mixed Items
            foreach ($request->items as $idx => $item) {
                $itemType = $item['item_type'];
                $itemId = $item['item_id'];

                if ($itemType === 'shortage') {
                    // SHORTAGE ITEM
                    $shortageId = (int) str_replace('SHORTAGE_', '', $itemId);
                    $shortage = ShortageItem::with('purchaseRequest.preShipping')->find($shortageId);

                    if (!$shortage) {
                        throw new \Exception("Shortage item #{$shortageId} not found");
                    }

                    // ⭐ GET ORIGINAL PRE_SHIPPING_ID dari shortage's purchase request
                    $preShippingId = $shortage->purchaseRequest->preShipping->id ?? null;

                    if (!$preShippingId) {
                        throw new \Exception("Shortage item #{$shortageId} has no associated pre-shipping");
                    }

                    // CREATE SHIPPING DETAIL dengan pre_shipping_id yang valid
                    ShippingDetail::create([
                        'shipping_id' => $shipping->id,
                        'pre_shipping_id' => $preShippingId, // ⭐ SET DARI ORIGINAL PR
                        'shortage_item_id' => $shortageId,
                        'percentage' => $request->percentage[$idx] ?? null,
                        'int_cost' => $request->int_cost[$idx],
                        'extra_cost' => $request->extra_cost[$idx] ?? 0,
                        'extra_cost_reason' => $request->extra_cost_reason[$idx] ?? null,
                        'destination' => $request->destination[$idx],
                    ]);

                    // UPDATE SHORTAGE STATUS
                    $shortage->update([
                        'status' => 'reshipped',
                        'resend_count' => $shortage->resend_count + 1,
                        'notes' => ($shortage->notes ? $shortage->notes . "\n" : '') . 'Reshipped on ' . now()->format('Y-m-d H:i:s') . " via Int. Waybill: {$shipping->international_waybill_no}",
                    ]);

                    $shortageItemCount++;

                    \Log::info('Shortage item added to shipping', [
                        'shortage_id' => $shortageId,
                        'pre_shipping_id' => $preShippingId,
                        'shipping_id' => $shipping->id,
                    ]);
                } else {
                    // NORMAL ITEM
                    $preShippingId = (int) $itemId;

                    ShippingDetail::create([
                        'shipping_id' => $shipping->id,
                        'pre_shipping_id' => $preShippingId, // ⭐ NORMAL PRE_SHIPPING_ID
                        'shortage_item_id' => null,
                        'percentage' => $request->percentage[$idx] ?? null,
                        'int_cost' => $request->int_cost[$idx],
                        'extra_cost' => $request->extra_cost[$idx] ?? 0,
                        'extra_cost_reason' => $request->extra_cost_reason[$idx] ?? null,
                        'destination' => $request->destination[$idx],
                    ]);

                    $normalItemCount++;
                }
            }

            // Commit transaction
            DB::commit();

            $successMessage = 'Shipping created successfully with ' . ucfirst($request->int_allocation_method) . ' allocation';
            if ($normalItemCount > 0) {
                $successMessage .= " | {$normalItemCount} normal item(s)";
            }
            if ($shortageItemCount > 0) {
                $successMessage .= " | {$shortageItemCount} shortage resend item(s)";
            }

            \Log::info('✅ Shipping created successfully', [
                'shipping_id' => $shipping->id,
                'message' => $successMessage,
            ]);

            return redirect()->route('shipping-management.index')->with('success', $successMessage);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error creating shipping: ' . $e->getMessage(), [
                'waybill' => $request->international_waybill_no,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create shipping: ' . $e->getMessage());
        }
    }
}
