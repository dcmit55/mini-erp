<?php

namespace App\Http\Controllers;

use App\Models\PreShipping;
use App\Models\PurchaseRequest;
use Illuminate\Http\Request;

class PreShippingController extends Controller
{
    public function index()
    {
        // Ambil semua purchase request yang sudah approved dan belum masuk shipping
        $requests = PurchaseRequest::with(['project', 'supplier', 'preShipping'])
            ->where('approval_status', 'Approved')
            ->get();

        // Pastikan setiap purchase_request yang lolos filter punya pre_shipping
        foreach ($requests as $req) {
            PreShipping::firstOrCreate(['purchase_request_id' => $req->id]);
        }

        // Ambil ulang, tapi hanya yang belum punya shippingDetail
        $requests = PurchaseRequest::with(['project', 'supplier', 'preShipping'])
            ->where('approval_status', 'Approved')
            ->whereHas('preShipping', function ($q) {
                $q->whereDoesntHave('shippingDetail');
            })
            ->get();

        return view('pre_shippings.index', compact('requests'));
    }

    public function quickUpdate(Request $request, $id)
    {
        $preShipping = PreShipping::where('purchase_request_id', $id)->firstOrFail();

        $request->validate([
            'domestic_waybill_no' => 'nullable|string|max:255',
            'same_supplier_selection' => 'nullable|boolean',
            'percentage_if_same_supplier' => 'nullable|numeric|min:0|max:100',
            'domestic_cost' => 'nullable|numeric|min:0',
        ]);

        $preShipping->update($request->only(['domestic_waybill_no', 'same_supplier_selection', 'percentage_if_same_supplier', 'domestic_cost']));

        return response()->json(['success' => true]);
    }
}
