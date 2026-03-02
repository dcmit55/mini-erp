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
            $normalPreShippings = PreShipping::with([
                'purchaseRequest' => function ($q) {
                    $q->with(['project', 'supplier', 'currency', 'originalSupplier', 'user', 'inventory']);
                },
                'shippingDetail',
            ])
                ->whereIn('group_key', $groupKeys)
                ->get();

            // Filter yang valid
            $normalPreShippings = $normalPreShippings->filter(function ($item) {
                return $item->purchaseRequest !== null && !empty($item->domestic_waybill_no) && !empty($item->domestic_cost);
            });
        }

        // STEP 2: Load Shortage Items dengan USER-FRIENDLY VALIDATION
        $shortageItems = collect();
        $validationErrors = []; // Collect user-facing errors
        $validationWarnings = []; // Collect warnings

        if (!empty($shortageItemIds)) {
            // Load shortage items dengan proper eager loading
            $shortageItems = ShortageItem::whereIn('id', $shortageItemIds)->resolvable()->get();

            // Load purchase requests
            $shortageItems->load([
                'purchaseRequest' => function ($query) {
                    $query->with([
                        'supplier',
                        'originalSupplier',
                        'project',
                        'currency',
                        'user',
                        'inventory',
                        'preShipping',
                    ]);
                },
            ]);

            // ENHANCED FILTER dengan USER-FACING ERROR MESSAGES
            $shortageItems = $shortageItems->filter(function ($shortage) use (&$validationErrors, &$validationWarnings) {
                // VALIDATION 1: PR not found
                if (!$shortage->purchaseRequest) {
                    $validationErrors[] = "Shortage ID #{$shortage->id}: Purchase Request not found or has been deleted.";

                    \Log::warning('Shortage item SKIPPED - PR not found', [
                        'shortage_id' => $shortage->id,
                        'pr_id' => $shortage->purchase_request_id,
                    ]);
                    return false;
                }

                $pr = $shortage->purchaseRequest;

                // VALIDATION 2: Supplier changed
                if ($pr->hasSupplierChanged()) {
                    $oldSupplier = $pr->originalSupplier ? $pr->originalSupplier->name : 'Unknown';
                    $newSupplier = $pr->supplier ? $pr->supplier->name : 'Unknown';

                    $validationErrors[] = "Material <b>{$pr->material_name}</b>: Supplier changed from <b>{$oldSupplier}</b> to <b>{$newSupplier}</b>. Cannot resend shortage to different supplier. Please create new PR instead.";

                    \Log::warning('Shortage item SKIPPED - Supplier changed', [
                        'shortage_id' => $shortage->id,
                        'material' => $pr->material_name,
                        'old_supplier' => $oldSupplier,
                        'new_supplier' => $newSupplier,
                    ]);
                    return false;
                }

                // VALIDATION 3: Supplier blacklisted
                if ($pr->supplier && $pr->supplier->status === 'blacklisted') {
                    $validationErrors[] = "Material <b>{$pr->material_name}</b>: Supplier <b>{$pr->supplier->name}</b> is blacklisted. Cannot create shipment.";

                    \Log::warning('Shortage item SKIPPED - Supplier blacklisted', [
                        'shortage_id' => $shortage->id,
                        'supplier' => $pr->supplier->name,
                    ]);
                    return false;
                }

                // WARNING: Supplier inactive
                if ($pr->supplier && $pr->supplier->status === 'inactive') {
                    $validationWarnings[] = "Material <b>{$pr->material_name}</b>: Supplier <b>{$pr->supplier->name}</b> is inactive. Shipment allowed but verify supplier status.";

                    \Log::info('Shortage item WARNING - Supplier inactive', [
                        'shortage_id' => $shortage->id,
                        'supplier' => $pr->supplier->name,
                    ]);
                }

                // VALIDATION 4: Supplier not found
                if (!$pr->supplier) {
                    $validationErrors[] = "Material <b>{$pr->material_name}</b>: Supplier not found (ID: {$pr->supplier_id}). Cannot create shipment.";

                    \Log::warning('Shortage item SKIPPED - Supplier not found', [
                        'shortage_id' => $shortage->id,
                        'supplier_id' => $pr->supplier_id,
                    ]);
                    return false;
                }

                // WARNING: Delivery date too old
                if ($pr->delivery_date) {
                    $daysSinceDelivery = now()->diffInDays($pr->delivery_date);
                    $maxDaysAllowed = 180; // 6 bulan

                    if ($daysSinceDelivery > $maxDaysAllowed) {
                        $validationWarnings[] = "Material <b>{$pr->material_name}</b>: Delivery date is <b>{$daysSinceDelivery} days old</b> (max recommended: {$maxDaysAllowed} days). Consider creating new PR.";

                        \Log::info('Shortage item WARNING - Delivery date old', [
                            'shortage_id' => $shortage->id,
                            'days_old' => $daysSinceDelivery,
                        ]);
                    }
                }

                // VALIDATION 5: Already in pending shipment
                $currentShippingDetail = ShippingDetail::where('shortage_item_id', $shortage->id)
                    ->whereHas('shipping', function ($q) {
                        $q->doesntHave('goodsReceive');
                    })
                    ->exists();

                if ($currentShippingDetail) {
                    $validationErrors[] = "Material <b>{$pr->material_name}</b>: Already in pending shipment. Cannot add to multiple shipments simultaneously.";

                    \Log::warning('Shortage item SKIPPED - Already in shipment', [
                        'shortage_id' => $shortage->id,
                        'material' => $pr->material_name,
                    ]);
                    return false;
                }

                // WARNING: Project inactive (if applicable)
                if ($pr->project && method_exists($pr->project, 'isActive') && !$pr->project->isActive()) {
                    $validationWarnings[] = "Material <b>{$pr->material_name}</b>: Project <b>{$pr->project->name}</b> is inactive. Verify project status.";

                    \Log::info('Shortage item WARNING - Project inactive', [
                        'shortage_id' => $shortage->id,
                        'project' => $pr->project->name,
                    ]);
                }

                // ALL VALIDATIONS PASSED
                \Log::info('Shortage item VALIDATED successfully', [
                    'shortage_id' => $shortage->id,
                    'material' => $pr->material_name,
                    'supplier' => $pr->supplier->name,
                ]);

                return true;
            });
        }

        // FLASH VALIDATION ERRORS TO SESSION (for UI display)
        if (!empty($validationErrors)) {
            session()->flash('validation_errors', $validationErrors);
        }

        if (!empty($validationWarnings)) {
            session()->flash('validation_warnings', $validationWarnings);
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
            $wrapper = (object) [
                'id' => 'SHORTAGE_' . $shortage->id,
                'shortage_item_id' => $shortage->id,
                'purchase_request_id' => $shortage->purchase_request_id,
                'purchaseRequest' => $shortage->purchaseRequest,
                'domestic_waybill_no' => $shortage->old_domestic_wbl,
                'domestic_cost' => 0,
                'allocated_cost' => 0,
                'is_shortage' => true,
                'item_type' => 'shortage',
                'shortage_qty' => $shortage->shortage_qty,
                'resend_count' => $shortage->resend_count,
            ];

            $allItems->push($wrapper);
        }

        // HANDLE CASE: Semua items gagal validasi
        if ($allItems->isEmpty()) {
            $errorMessage = 'No valid items found. ';

            if (!empty($validationErrors)) {
                $errorMessage .= 'Please fix the following issues and try again.';
            } else {
                $errorMessage .= 'All items failed validation.';
            }

            return redirect()->route('pre-shippings.index')->with('error', $errorMessage);
        }

        // SUMMARY MESSAGE untuk user
        $normalCount = $normalPreShippings->count();
        $shortageCount = $shortageItems->count();
        $skippedCount = count($shortageItemIds) - $shortageCount;

        $summaryMessage = "Creating shipping with <b>{$normalCount} normal item(s)</b>";
        if ($shortageCount > 0) {
            $summaryMessage .= " and <b>{$shortageCount} shortage resend item(s)</b>";
        }
        if ($skippedCount > 0) {
            $summaryMessage .= " (<b>{$skippedCount} item(s) skipped</b> - see details below)";
        }

        session()->flash('info', $summaryMessage);

        $freightCompanies = ['DHL', 'FedEx', 'Maersk', 'CMA CGM'];

        return view('procurement.shippings.create', compact('allItems', 'freightCompanies'))->with('preShippings', $allItems);
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

        // START TRANSACTION IMMEDIATELY - BEFORE ANY CREATE
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

                    // GET ORIGINAL PRE_SHIPPING_ID dari shortage's purchase request
                    $preShippingId = $shortage->purchaseRequest->preShipping->id ?? null;

                    if (!$preShippingId) {
                        throw new \Exception("Shortage item #{$shortageId} has no associated pre-shipping");
                    }

                    // CREATE SHIPPING DETAIL dengan pre_shipping_id yang valid
                    ShippingDetail::create([
                        'shipping_id' => $shipping->id,
                        'pre_shipping_id' => $preShippingId, // SET DARI ORIGINAL PR
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
                        'pre_shipping_id' => $preShippingId,
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
