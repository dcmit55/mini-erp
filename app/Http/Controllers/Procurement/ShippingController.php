<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Procurement\PreShipping;
use App\Models\Procurement\Shipping;
use App\Models\Procurement\ShippingDetail;
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

        $groupKeys = $request->input('group_keys');
        if (is_string($groupKeys)) {
            $groupKeys = json_decode($groupKeys, true);
        }

        if (empty($groupKeys)) {
            return redirect()->route('pre-shippings.index')->with('error', 'Please select at least one group');
        }

        // Cek Domestic Waybill No dan Domestic Cost
        $preShippings = PreShipping::with(['purchaseRequest.project', 'purchaseRequest.supplier', 'purchaseRequest.currency', 'shippingDetail'])
            ->whereIn('group_key', $groupKeys)
            ->get();

        // Validasi domestic_waybill_no dan domestic_cost tidak boleh kosong
        $validPreShippings = $preShippings->filter(function ($item) {
            return $item->purchaseRequest !== null && !empty($item->domestic_waybill_no) && !empty($item->domestic_cost);
        });

        // Jika ada yang kosong, berikan pesan error detail
        $incompleteItems = $preShippings->filter(function ($item) {
            return $item->purchaseRequest !== null && (empty($item->domestic_waybill_no) || empty($item->domestic_cost));
        });

        if (!$incompleteItems->isEmpty()) {
            $incompleteList = $incompleteItems
                ->map(function ($item) {
                    $missing = [];
                    if (empty($item->domestic_waybill_no)) {
                        $missing[] = 'Domestic Waybill No';
                    }
                    if (empty($item->domestic_cost)) {
                        $missing[] = 'Domestic Cost';
                    }

                    return $item->purchaseRequest->material_name . ' (' . implode(', ', $missing) . ' missing)';
                })
                ->implode(', ');

            return redirect()
                ->route('pre-shippings.index')
                ->with('error', 'Cannot proceed to shipping. The following items are incomplete: ' . $incompleteList . '. Please fill in all required fields (Domestic Waybill No & Domestic Cost) before proceeding.');
        }

        if ($validPreShippings->isEmpty()) {
            return redirect()->route('pre-shippings.index')->with('error', 'No valid pre-shipping data found. Some items may have been deleted.');
        }

        // Notifikasi jika ada yang di-filter
        if ($validPreShippings->count() < $preShippings->count()) {
            $skippedCount = $preShippings->count() - $validPreShippings->count();
            session()->flash('warning', "{$skippedCount} pre-shipping item(s) were skipped.");
        }

        $freightCompanies = ['DHL', 'FedEx', 'Maersk', 'CMA CGM'];

        return view('procurement.shippings.create', compact('validPreShippings', 'freightCompanies'))->with('preShippings', $validPreShippings);
    }

    public function store(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            return redirect()->route('pre-shippings.index')->with('error', 'You do not have permission to create shipping.');
        }

        // ⭐ UPDATED VALIDATION: Tambah freight_method dan extra cost fields
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
            // Create shipping record with freight_method
            $shipping = Shipping::create([
                'international_waybill_no' => $request->international_waybill_no,
                'freight_company' => $request->freight_company,
                'freight_method' => $request->freight_method, // ⭐ NEW
                'freight_price' => $request->freight_price,
                'eta_to_arrived' => $request->eta_to_arrived,
            ]);

            // Simpan shipping details dengan extra cost
            foreach ($request->pre_shipping_ids as $idx => $preShippingId) {
                ShippingDetail::create([
                    'shipping_id' => $shipping->id,
                    'pre_shipping_id' => $preShippingId,
                    'percentage' => $request->percentage[$idx] ?? null,
                    'int_cost' => $request->int_cost[$idx], // Base allocated cost
                    'extra_cost' => $request->extra_cost[$idx] ?? 0, // ⭐ NEW
                    'extra_cost_reason' => $request->extra_cost_reason[$idx] ?? null, // ⭐ NEW
                    'destination' => $request->destination[$idx],
                ]);
            }

            DB::commit();

            // Success message with freight method info
            $successMessage = 'Shipping created successfully with cost allocation method: ' . ucfirst($request->int_allocation_method);

            if ($request->freight_method === 'Air Freight') {
                $totalExtraCost = array_sum($request->extra_cost ?? []);
                if ($totalExtraCost > 0) {
                    $successMessage .= ' | Air Freight with extra cost: ' . number_format($totalExtraCost, 2);
                }
            }

            return redirect()->route('shipping-management.index')->with('success', $successMessage);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            // Handle duplicate entry error
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
