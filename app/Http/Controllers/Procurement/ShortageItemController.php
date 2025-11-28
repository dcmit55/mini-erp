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
