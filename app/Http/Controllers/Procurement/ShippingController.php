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

            // Filter yang valid (has domestic waybill & cost)
            $normalPreShippings = $normalPreShippings->filter(function ($item) {
                return $item->purchaseRequest !== null && !empty($item->domestic_waybill_no) && !empty($item->domestic_cost);
            });
        }

        // STEP 2: Load Shortage Items & Convert to PreShipping-like Structure
        $shortageAsPreShippings = collect();
        if (!empty($shortageItemIds)) {
            $shortageItems = ShortageItem::with(['purchaseRequest.project', 'purchaseRequest.supplier', 'purchaseRequest.currency'])
                ->whereIn('id', $shortageItemIds)
                ->resolvable() // Only resolvable items
                ->get();

            // Convert ShortageItem to PreShipping-like object
            foreach ($shortageItems as $shortage) {
                if (!$shortage->purchaseRequest) {
                    continue; // Skip if PR missing
                }

                // Create virtual PreShipping object
                $virtualPreShipping = new PreShipping([
                    'purchase_request_id' => $shortage->purchase_request_id,
                    'group_key' => 'SHORTAGE_' . $shortage->id,
                    'domestic_waybill_no' => $shortage->old_domestic_wbl,
                    'domestic_cost' => 0, // Shortage tidak punya domestic cost
                    'cost_allocation_method' => 'value',
                ]);

                // Attach purchaseRequest relation
                $virtualPreShipping->setRelation('purchaseRequest', $shortage->purchaseRequest);

                // Mark as shortage untuk identification
                $virtualPreShipping->is_shortage = true;
                $virtualPreShipping->shortage_item_id = $shortage->id;
                $virtualPreShipping->shortage_qty = $shortage->shortage_qty;

                $shortageAsPreShippings->push($virtualPreShipping);
            }
        }

        // STEP 3: Merge Normal + Shortage Items
        $validPreShippings = $normalPreShippings->merge($shortageAsPreShippings);

        if ($validPreShippings->isEmpty()) {
            return redirect()->route('pre-shippings.index')->with('error', 'No valid items found. Some items may have incomplete data or been deleted.');
        }

        // Notifikasi summary
        $normalCount = $normalPreShippings->count();
        $shortageCount = $shortageAsPreShippings->count();
        $summaryMessage = "Creating shipping with {$normalCount} normal item(s)";
        if ($shortageCount > 0) {
            $summaryMessage .= " and {$shortageCount} shortage item(s)";
        }
        session()->flash('info', $summaryMessage);

        $freightCompanies = ['DHL', 'FedEx', 'Maersk', 'CMA CGM'];

        return view('procurement.shippings.create', compact('validPreShippings', 'freightCompanies'))->with('preShippings', $validPreShippings);
    }

    public function store(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            return redirect()->route('pre-shippings.index')->with('error', 'You do not have permission to create shipping.');
        }

        $request->validate(
            [
                'international_waybill_no' => 'required|string|max:255|unique:shippings,international_waybill_no',
                'freight_company' => 'required|string|max:255',
                'freight_method' => 'required|in:Sea Freight,Air Freight',
                'freight_price' => 'required|numeric|min:0',
                'eta_to_arrived' => 'required|date',
                'pre_shipping_ids' => 'required|array|min:1',
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
                'is_shortage' => 'nullable|array',
                'is_shortage.*' => 'nullable|boolean',
                'shortage_item_ids' => 'nullable|array',
                'shortage_item_ids.*' => 'nullable|exists:shortage_items,id',
            ],
            [
                'international_waybill_no.required' => 'International Waybill Number is required.',
                'international_waybill_no.unique' => 'This International Waybill Number has already been used. Please use a different number.',
                'freight_company.required' => 'International Freight Company is required.',
                'freight_method.required' => 'International Freight Method is required.',
            ],
        );

        // Validasi percentage total jika method = percentage
        if ($request->int_allocation_method === 'percentage') {
            $totalPercentage = array_sum($request->percentage ?? []);

            if (abs($totalPercentage - 100) > 0.5) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['percentage' => "Total percentage must be close to 100%. Current total: {$totalPercentage}%"]);
            }
        }

        DB::beginTransaction();
        try {
            // Create shipping record
            $shipping = Shipping::create([
                'international_waybill_no' => $request->international_waybill_no,
                'freight_company' => $request->freight_company,
                'freight_method' => $request->freight_method,
                'freight_price' => $request->freight_price,
                'eta_to_arrived' => $request->eta_to_arrived,
            ]);

            $normalItemCount = 0;
            $shortageItemCount = 0;

            // STEP: Save Shipping Details (Mixed Normal + Shortage)
            foreach ($request->pre_shipping_ids as $idx => $preShippingId) {
                $isShortage = $request->is_shortage[$idx] ?? false;

                // Create shipping detail (sama untuk normal & shortage)
                ShippingDetail::create([
                    'shipping_id' => $shipping->id,
                    'pre_shipping_id' => $preShippingId,
                    'percentage' => $request->percentage[$idx] ?? null,
                    'int_cost' => $request->int_cost[$idx],
                    'extra_cost' => $request->extra_cost[$idx] ?? 0,
                    'extra_cost_reason' => $request->extra_cost_reason[$idx] ?? null,
                    'destination' => $request->destination[$idx],
                ]);

                // Update shortage item status if this is shortage resend
                if ($isShortage && isset($request->shortage_item_ids[$idx])) {
                    $shortageItemId = $request->shortage_item_ids[$idx];
                    $shortage = ShortageItem::find($shortageItemId);

                    if ($shortage) {
                        $shortage->update([
                            'status' => 'reshipped',
                            'notes' => ($shortage->notes ? $shortage->notes . "\n" : '') . 'Shipped on ' . now()->format('Y-m-d H:i:s') . " via International Waybill: {$shipping->international_waybill_no}",
                        ]);

                        $shortageItemCount++;

                        \Log::info('Shortage item shipped', [
                            'shortage_id' => $shortageItemId,
                            'shipping_id' => $shipping->id,
                            'international_waybill_no' => $shipping->international_waybill_no,
                        ]);
                    }
                } else {
                    $normalItemCount++;
                }
            }

            DB::commit();

            // SUCCESS MESSAGE dengan breakdown
            $successMessage = 'Shipping created successfully with cost allocation method: ' . ucfirst($request->int_allocation_method);

            if ($normalItemCount > 0) {
                $successMessage .= " | {$normalItemCount} normal item(s)";
            }

            if ($shortageItemCount > 0) {
                $successMessage .= " | {$shortageItemCount} shortage resend item(s)";
            }

            if ($request->freight_method === 'Air Freight') {
                $totalExtraCost = array_sum($request->extra_cost ?? []);
                if ($totalExtraCost > 0) {
                    $successMessage .= ' | Air Freight with extra cost: ' . number_format($totalExtraCost, 2);
                }
            }

            return redirect()->route('shipping-management.index')->with('success', $successMessage);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            if ($e->getCode() == 23000) {
                \Log::error('Duplicate waybill number attempt: ' . $request->international_waybill_no);

                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['international_waybill_no' => 'This International Waybill Number has already been used. Please use a different number.']);
            }

            \Log::error('Error creating shipping: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create shipping: ' . $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating shipping: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create shipping: ' . $e->getMessage());
        }
    }
}
