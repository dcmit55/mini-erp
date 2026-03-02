<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Procurement\PreShipping;
use App\Models\Procurement\PurchaseRequest;
use App\Models\Procurement\ShortageItem;
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

    /**
     * Display pre-shipping index with filter support
     */
    public function index()
    {
        // Eager load ALL necessary relations di awal
        $approvedRequests = PurchaseRequest::with(['project', 'supplier', 'preShipping.shippingDetail', 'currency'])
            ->where('approval_status', 'Approved')
            ->whereNotNull('supplier_id')
            ->where('supplier_id', '>', 0)
            ->whereNotNull('delivery_date')
            ->where('delivery_date', '!=', '')
            ->get();

        // Filter data SEBELUM generate untuk safety
        $validRequests = $approvedRequests->filter(function ($pr) {
            return $pr->supplier_id && $pr->delivery_date && !$pr->hasBeenShipped();
        });

        // Log untuk debugging
        \Log::info('Pre-Shipping Index - Approved Requests', [
            'total_approved' => $approvedRequests->count(),
            'valid_for_pre_shipping' => $validRequests->count(),
            'filtered_out' => $approvedRequests->count() - $validRequests->count(),
        ]);

        // Auto generate pre-shipping dengan grouping
        $this->generatePreShippingGroups($validRequests);

        // Ambil data yang sudah di-group untuk ditampilkan
        $groupedPreShippings = $this->getGroupedPreShippings();

        return view('procurement.pre_shippings.index', compact('groupedPreShippings'));
    }

    /**
     * Generate pre-shipping groups dengan validation lebih ketat
     */
    private function generatePreShippingGroups($approvedRequests)
    {
        $groups = [];

        // Group by supplier and delivery date
        foreach ($approvedRequests as $request) {
            // Relax validation - hanya check supplier_id & delivery_date EXISTS
            if (!$request->supplier_id || !$request->delivery_date) {
                \Log::warning('PR skipped in generatePreShippingGroups - missing required data', [
                    'pr_id' => $request->id,
                    'supplier_id' => $request->supplier_id,
                    'delivery_date' => $request->delivery_date,
                    'material_name' => $request->material_name,
                    'approval_status' => $request->approval_status,
                ]);
                continue;
            }

            $groupKey = PreShipping::generateGroupKey($request->supplier_id, $request->delivery_date);

            $groups[$groupKey][] = $request;
        }

        // ✅ FIX: Use firstOrCreate to prevent race condition
        foreach ($groups as $groupKey => $requests) {
            foreach ($requests as $request) {
                // Atomic operation - prevents duplicate creation
                $preShipping = PreShipping::firstOrCreate(
                    [
                        'purchase_request_id' => $request->id, // Unique constraint
                    ],
                    [
                        'group_key' => $groupKey,
                        'cost_allocation_method' => 'value',
                    ],
                );

                // Jika record sudah ada tapi group_key berbeda, update
                if ($preShipping->wasRecentlyCreated) {
                    \Log::info('PreShipping created', [
                        'pre_shipping_id' => $preShipping->id,
                        'purchase_request_id' => $request->id,
                        'group_key' => $groupKey,
                        'material_name' => $request->material_name,
                    ]);
                } else {
                    // Record sudah ada, check if group_key needs update
                    if ($preShipping->group_key !== $groupKey) {
                        $preShipping->update(['group_key' => $groupKey]);

                        \Log::info('PreShipping group_key updated', [
                            'pre_shipping_id' => $preShipping->id,
                            'old_group_key' => $preShipping->getOriginal('group_key'),
                            'new_group_key' => $groupKey,
                        ]);
                    }
                }
            }
        }

        // Log summary untuk monitoring
        \Log::info('generatePreShippingGroups completed', [
            'total_groups' => count($groups),
            'total_items' => array_sum(array_map('count', $groups)),
        ]);
    }

    /**
     * Get grouped pre-shipping data TANPA N+1 queries
     */
    private function getGroupedPreShippings()
    {
        // Load ALL relations di satu query
        $preShippings = PreShipping::with([
            'purchaseRequest' => function ($query) {
                $query->with(['project', 'supplier', 'currency']);
            },
            'shippingDetail.shipping',
        ])
            ->whereHas('purchaseRequest')
            ->get();

        // Group by group_key dengan NULL safety
        $grouped = $preShippings
            ->groupBy('group_key')
            ->map(function ($group) {
                $firstItem = $group->first();

                // Null safety checks
                if (!$firstItem || !$firstItem->purchaseRequest) {
                    \Log::warning('PreShipping with missing purchaseRequest', [
                        'pre_shipping_id' => $firstItem ? $firstItem->id : 'unknown',
                        'group_key' => $firstItem ? $firstItem->group_key : 'unknown',
                    ]);
                    return null;
                }

                // Check hasBeenShipped TANPA additional query
                // Data sudah di-eager load, jadi tidak perlu query lagi
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
            ->filter() // Remove null values
            ->values();

        // Sort: not shipped di atas, shipped di bawah
        return $grouped->sortBy(function ($group) {
            return $group['has_been_shipped'] ? 1 : 0;
        });
    }

    /**
     * Quick update dengan optimized query
     */
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
            // Eager load purchaseRequest untuk avoid N+1
            $groupItems = PreShipping::with([
                'purchaseRequest' => function ($query) {
                    $query->with(['project', 'supplier']);
                },
            ])
                ->where('group_key', $groupKey)
                ->get();

            if ($groupItems->isEmpty()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Group not found',
                    ],
                    404,
                );
            }

            // Handle percentage validation
            if ($request->cost_allocation_method === 'percentage') {
                if ($request->has('percentages') && !empty(array_filter($request->percentages))) {
                    $totalPercentage = array_sum($request->percentages);

                    if (abs($totalPercentage - 100) > 5) {
                        return response()->json(
                            [
                                'success' => false,
                                'message' => 'Total percentage should be close to 100%. Current total: ' . number_format($totalPercentage, 2) . '%',
                                'warning' => true,
                            ],
                            400,
                        );
                    }
                } else {
                    // Auto-distribute percentages
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
                        $equalPercentage = 100 / $groupItems->count();
                        $autoPercentages = array_fill(0, $groupItems->count(), $equalPercentage);
                    }

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

                if ($request->cost_allocation_method === 'percentage') {
                    $percentage = $request->percentages[$index] ?? 0;
                    $itemUpdateData['allocation_percentage'] = $percentage;
                }

                $item->update($itemUpdateData);

                $allocatedCost = $item->fresh()->calculateAllocatedCost();
                $item->update(['allocated_cost' => $allocatedCost]);

                $updatedItems[] = [
                    'id' => $item->id,
                    'index' => $index,
                    'allocated_cost' => $allocatedCost,
                    'allocation_percentage' => $item->fresh()->allocation_percentage,
                    'cost_allocation_method' => $item->fresh()->cost_allocation_method,
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'updated_items' => $updatedItems,
                'auto_percentages' => $request->percentages ?? [],
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

    public function checkOrphanedPRs()
    {
        // Get all Approved PRs yang TIDAK punya PreShipping
        $orphanedPRs = PurchaseRequest::with(['supplier', 'project'])
            ->where('approval_status', 'Approved')
            ->doesntHave('preShipping')
            ->whereNotNull('supplier_id')
            ->whereNotNull('delivery_date')
            ->get();

        return response()->json([
            'orphaned_count' => $orphanedPRs->count(),
            'orphaned_prs' => $orphanedPRs->map(function ($pr) {
                return [
                    'id' => $pr->id,
                    'material_name' => $pr->material_name,
                    'supplier_id' => $pr->supplier_id,
                    'supplier_name' => $pr->supplier ? $pr->supplier->name : 'DELETED',
                    'delivery_date' => $pr->delivery_date,
                    'remark' => $pr->remark,
                ];
            }),
        ]);
    }
}
