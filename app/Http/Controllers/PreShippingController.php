<?php

namespace App\Http\Controllers;

use App\Models\PreShipping;
use App\Models\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PreShippingController extends Controller
{
    public function index()
    {
        // Ambil semua purchase request yang sudah approved dan belum masuk shipping
        $approvedRequests = PurchaseRequest::with(['project', 'supplier', 'preShipping'])
            ->where('approval_status', 'Approved')
            ->whereNotNull('supplier_id')
            ->whereNotNull('delivery_date')
            ->get();

        // Auto generate pre-shipping dengan grouping
        $this->generatePreShippingGroups($approvedRequests);

        // Ambil data yang sudah di-group untuk ditampilkan
        $groupedPreShippings = $this->getGroupedPreShippings();

        return view('pre_shippings.index', compact('groupedPreShippings'));
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
                PreShipping::updateOrCreate(
                    ['purchase_request_id' => $request->id],
                    [
                        'group_key' => $groupKey,
                        'cost_allocation_method' => 'quantity', // default method
                    ],
                );
            }
        }
    }

    private function getGroupedPreShippings()
    {
        // Eager load semua relasi yang diperlukan sekaligus
        $preShippings = PreShipping::with([
            'purchaseRequest.project', // Eager load project
            'purchaseRequest.supplier', // Eager load supplier
        ])
            ->whereDoesntHave('shippingDetail')
            ->get();

        // Group by group_key
        return $preShippings->groupBy('group_key')->map(function ($group) {
            $firstItem = $group->first();

            return [
                'group_key' => $firstItem->group_key,
                'supplier' => $firstItem->purchaseRequest->supplier,
                'delivery_date' => $firstItem->purchaseRequest->delivery_date,
                'domestic_waybill_no' => $firstItem->domestic_waybill_no,
                'domestic_cost' => $firstItem->domestic_cost,
                'cost_allocation_method' => $firstItem->cost_allocation_method ?? 'quantity',
                // Items sudah ter-eager load, tidak perlu query lagi
                'items' => $group,
                'total_items' => $group->count(),
                'total_quantity' => $group->sum(function ($item) {
                    return $item->purchaseRequest->required_quantity ?? 0;
                }),
                'total_value' => $group->sum(function ($item) {
                    $qty = $item->purchaseRequest->required_quantity ?? 0;
                    $price = $item->purchaseRequest->price_per_unit ?? 0;
                    return $qty * $price;
                }),
            ];
        });
    }

    public function quickUpdate(Request $request, $groupKey)
    {
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
            // **PERBAIKAN**: Eager load untuk menghindari N+1
            $groupItems = PreShipping::with(['purchaseRequest.project', 'purchaseRequest.supplier'])
                ->where('group_key', $groupKey)
                ->get();

            if ($groupItems->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Group not found'], 404);
            }

            // Validate percentage total if method is percentage
            if ($request->cost_allocation_method === 'percentage') {
                if (!$request->has('percentages') || empty($request->percentages)) {
                    return response()->json(
                        [
                            'success' => false,
                            'message' => 'Percentages are required when using percentage method',
                        ],
                        422,
                    );
                }

                $totalPercentage = array_sum($request->percentages);
                if (abs($totalPercentage - 100) > 0.1) {
                    return response()->json(
                        [
                            'success' => false,
                            'message' => "Total percentage must equal 100%. Current total: {$totalPercentage}%",
                        ],
                        422,
                    );
                }
            }

            $updatedItems = [];

            // **OPTIMISASI**: Batch update untuk performa yang lebih baik
            $updateData = [];
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

                if ($request->cost_allocation_method === 'percentage' && isset($request->percentages[$index])) {
                    $itemUpdateData['allocation_percentage'] = $request->percentages[$index];
                }

                // Update item
                $item->update($itemUpdateData);

                // Calculate allocated cost menggunakan data yang sudah di-eager load
                $allocatedCost = $item->calculateAllocatedCost();
                $item->update(['allocated_cost' => $allocatedCost]);

                $updatedItems[] = [
                    'id' => $item->id,
                    'allocated_cost' => $allocatedCost,
                    'cost_allocation_method' => $item->cost_allocation_method,
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'updated_items' => $updatedItems,
                'message' => 'Group updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error updating pre-shipping: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }
}
