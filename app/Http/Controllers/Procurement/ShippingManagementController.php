<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Procurement\Shipping;
use Illuminate\Support\Facades\Auth;

class ShippingManagementController extends Controller
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
        $shippings = Shipping::with(['details.preShipping.purchaseRequest.project', 'details.preShipping.purchaseRequest.supplier'])
            ->orderByDesc('created_at')
            ->get();

        return view('procurement.shipping_management.index', compact('shippings'));
    }
    public function detail($id)
    {
        $shipping = Shipping::with(['details.preShipping.purchaseRequest.project', 'details.preShipping.purchaseRequest.supplier'])->findOrFail($id);

        $details = [];
        foreach ($shipping->details as $detail) {
            $details[] = [
                'purchase_type' => ucfirst(str_replace('_', ' ', $detail->preShipping->purchaseRequest->type)),
                'project_name' => $detail->preShipping->purchaseRequest->project->name ?? '-',
                'material_name' => $detail->preShipping->purchaseRequest->material_name,
                'supplier_name' => $detail->preShipping->purchaseRequest->supplier->name ?? '-',
                'unit_price' => $detail->preShipping->purchaseRequest->price_per_unit,
                'domestic_waybill_no' => $detail->preShipping->domestic_waybill_no,
                'purchased_qty' => $detail->preShipping->purchaseRequest->required_quantity,
            ];
        }

        return response()->json([
            'shipping' => $shipping,
            'details' => $details,
        ]);
    }
}
