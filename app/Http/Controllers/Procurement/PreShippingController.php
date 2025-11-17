<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Procurement\PreShipping;
use App\Models\Procurement\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PreShippingController extends Controller
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
        // Ambil semua purchase request yang sudah approved dan belum masuk shipping
        $approvedRequests = PurchaseRequest::with(['project', 'supplier', 'preShipping', 'currency'])
            ->where('approval_status', 'Approved')
            ->whereNotNull('supplier_id')
            ->whereNotNull('delivery_date')
            ->get();

        // Auto generate pre-shipping dengan grouping (TANPA OVERRIDE)
        $this->generatePreShippingGroups($approvedRequests);

        // Ambil data yang sudah di-group untuk ditampilkan
        $groupedPreShippings = $this->getGroupedPreShippings();

        return view('procurement.pre_shippings.index', compact('groupedPreShippings'));
    }

    private function generatePreShippingGroups($approvedRequests)
    {
        $groups = [];

        // Group by supplier and delivery date
        foreach ($approvedRequests as $request) {
            $groupKey = PreShipping::generateGroupKey($request->supplier_id, $request->delivery_date);
            $groups[$groupKey][] = $request;
        }

        // Create or update pre-shipping records
        foreach ($groups as $groupKey => $requests) {
            foreach ($requests as $request) {
                $existingPreShipping = PreShipping::where('purchase_request_id', $request->id)->first();

                if (!$existingPreShipping) {
                    // Hanya buat baru jika belum ada, default ke 'value' bukan 'quantity'
                    PreShipping::create([
                        'purchase_request_id' => $request->id,
                        'group_key' => $groupKey,
                        'cost_allocation_method' => 'value', // **DEFAULT KE 'value'**
                    ]);
                } else {
                    // Jika sudah ada, JANGAN override cost_allocation_method
                    // Hanya update group_key jika berbeda
                    if ($existingPreShipping->group_key !== $groupKey) {
                        $existingPreShipping->update(['group_key' => $groupKey]);
                    }
                    // JANGAN OVERRIDE cost_allocation_method yang sudah di-set user
                }
            }
        }
    }

    private function getGroupedPreShippings()
    {
        // ⭐ PERBAIKAN: Load dengan condition whereNotNull
        $preShippings = PreShipping::with(['purchaseRequest.project', 'purchaseRequest.supplier', 'purchaseRequest.currency', 'shippingDetail'])
            ->whereHas('purchaseRequest') // ⭐ FILTER: Hanya yang masih punya purchaseRequest
            ->get();

        // Group by group_key
        $grouped = $preShippings
            ->groupBy('group_key')
            ->map(function ($group) {
                $firstItem = $group->first();

                // ⭐ CEK NULL: Jika purchaseRequest null, skip
                if (!$firstItem->purchaseRequest) {
                    return null;
                }

                $hasBeenShipped = $group->contains(function ($item) {
                    return $item->shippingDetail !== null;
                });

                return [
                    'group_key' => $firstItem->group_key,
                    'supplier' => $firstItem->purchaseRequest->supplier,
                    'delivery_date' => $firstItem->purchaseRequest->delivery_date,
                    'domestic_waybill_no' => $firstItem->domestic_waybill_no,
                    'domestic_cost' => $firstItem->domestic_cost,
                    'cost_allocation_method' => $firstItem->cost_allocation_method ?? 'value',
                    'items' => $group,
                    'total_items' => $group->count(),
                    'total_quantity' => $group->sum(function ($item) {
                        return $item->purchaseRequest->qty_to_buy ?? ($item->purchaseRequest->required_quantity ?? 0);
                    }),
                    'total_value' => $group->sum(function ($item) {
                        $qty = $item->purchaseRequest->qty_to_buy ?? ($item->purchaseRequest->required_quantity ?? 0);
                        $price = $item->purchaseRequest->price_per_unit ?? 0;
                        return $qty * $price;
                    }),
                    'has_been_shipped' => $hasBeenShipped,
                ];
            })
            ->filter() // ⭐ HAPUS null entries dari map
            ->values(); // ⭐ Re-index array

        // Data yang belum shipped di atas, yang sudah shipped di bawah
        return $grouped->sortBy(function ($group) {
            return $group['has_been_shipped'] ? 1 : 0;
        });
    }

    public function quickUpdate(Request $request, $groupKey)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'You do not have permission to update pre-shipping data.',
                ],
                403,
            );
        }

        try {
            $request->validate([
                'domestic_waybill_no' => 'nullable|string|max:255',
                'domestic_cost' => 'nullable|numeric|min:0',
                'cost_allocation_method' => 'nullable|in:quantity,percentage,value',
                'percentages' => 'nullable|array',
                'percentages.*' => 'nullable|numeric|min:0|max:100',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Validation failed: ' . collect($e->errors())->flatten()->implode(', '),
                ],
                422,
            );
        }

        DB::beginTransaction();
        try {
            $groupItems = PreShipping::with(['purchaseRequest.project', 'purchaseRequest.supplier'])
                ->where('group_key', $groupKey)
                ->get();

            if ($groupItems->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Group not found'], 404);
            }

            // Lebih flexible validation untuk percentage
            if ($request->cost_allocation_method === 'percentage') {
                // Jika baru switch ke percentage mode, izinkan tanpa percentages dulu
                if ($request->has('percentages') && !empty(array_filter($request->percentages))) {
                    $totalPercentage = array_sum($request->percentages);

                    // Toleransi yang lebih besar dan pesan yang lebih informatif
                    if (abs($totalPercentage - 100) > 5) {
                        // Toleransi 5% untuk UX yang lebih baik
                        return response()->json(
                            [
                                'success' => false,
                                'message' => 'Total percentage should be close to 100%. Current total: ' . number_format($totalPercentage, 2) . '%',
                                'warning' => true, // Flag untuk menunjukkan ini warning, bukan error fatal
                            ],
                            400,
                        );
                    }
                }
                // Jika tidak ada percentages, set default yang reasonable
                elseif (!$request->has('percentages') || empty(array_filter($request->percentages))) {
                    // Auto-distribute percentage berdasarkan value ratio
                    // PERUBAHAN: Gunakan qty_to_buy untuk perhitungan
                    $totalValue = $groupItems->sum(function ($item) {
                        $qty = $item->purchaseRequest->qty_to_buy ?? ($item->purchaseRequest->required_quantity ?? 0);
                        $price = $item->purchaseRequest->price_per_unit ?? 0;
                        return $qty * $price;
                    });

                    $autoPercentages = [];
                    if ($totalValue > 0) {
                        foreach ($groupItems as $item) {
                            $itemQty = $item->purchaseRequest->qty_to_buy ?? ($item->purchaseRequest->required_quantity ?? 0);
                            $itemValue = $itemQty * ($item->purchaseRequest->price_per_unit ?? 0);
                            $autoPercentages[] = ($itemValue / $totalValue) * 100;
                        }
                    } else {
                        // Fallback: equal distribution
                        $equalPercentage = 100 / $groupItems->count();
                        $autoPercentages = array_fill(0, $groupItems->count(), $equalPercentage);
                    }

                    // Override request percentages with auto-calculated
                    $request->merge(['percentages' => $autoPercentages]);
                }
            }

            $updatedItems = [];

            foreach ($groupItems as $index => $item) {
                $itemUpdateData = [];

                if ($request->has('domestic_waybill_no')) {
                    $itemUpdateData['domestic_waybill_no'] = $request->domestic_waybill_no;
                }

                if ($request->has('domestic_cost')) {
                    $itemUpdateData['domestic_cost'] = $request->domestic_cost;
                }

                if ($request->has('cost_allocation_method')) {
                    $itemUpdateData['cost_allocation_method'] = $request->cost_allocation_method;

                    if ($request->cost_allocation_method !== 'percentage') {
                        $itemUpdateData['allocation_percentage'] = null;
                    }
                }

                // Set percentage dengan auto-calculation
                if ($request->cost_allocation_method === 'percentage') {
                    $percentage = $request->percentages[$index] ?? 0;
                    $itemUpdateData['allocation_percentage'] = $percentage;
                }

                // Update item
                $item->update($itemUpdateData);

                // Calculate allocated cost
                $allocatedCost = $item->fresh()->calculateAllocatedCost();
                $item->update(['allocated_cost' => $allocatedCost]);

                $updatedItems[] = [
                    'id' => $item->id,
                    'index' => $index, // Include index for frontend matching
                    'allocated_cost' => $allocatedCost,
                    'allocation_percentage' => $item->fresh()->allocation_percentage,
                    'cost_allocation_method' => $item->fresh()->cost_allocation_method,
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'updated_items' => $updatedItems,
                'auto_percentages' => $request->percentages ?? [], // Return auto-calculated percentages
                'message' => 'Group updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('PreShipping quickUpdate error', [
                'group_key' => $groupKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Server error occurred. Please try again.',
                ],
                500,
            );
        }
    }
}
