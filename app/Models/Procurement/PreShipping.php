<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use App\Models\Procurement\PurchaseRequest;
use App\Models\Procurement\ShippingDetail;

class PreShipping extends Model
{
    protected $fillable = ['purchase_request_id', 'group_key', 'domestic_waybill_no', 'same_supplier_selection', 'percentage_if_same_supplier', 'domestic_cost', 'cost_allocation_method', 'allocation_percentage', 'allocated_cost'];

    protected $casts = [
        'same_supplier_selection' => 'boolean',
        'percentage_if_same_supplier' => 'decimal:2',
        'domestic_cost' => 'decimal:2',
        'allocation_percentage' => 'decimal:2',
        'allocated_cost' => 'decimal:2',
    ];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function shippingDetail()
    {
        return $this->hasOne(ShippingDetail::class);
    }

    public function hasBeenShipped()
    {
        return $this->shippingDetail()->exists();
    }

    public function groupItems()
    {
        return self::with(['purchaseRequest.project', 'purchaseRequest.supplier'])
            ->where('group_key', $this->group_key)
            ->get();
    }

    public static function generateGroupKey($supplierId, $deliveryDate)
    {
        return 'GRP_' . $supplierId . '_' . date('Ymd', strtotime($deliveryDate));
    }

    public function calculateAllocatedCost()
    {
        if (!$this->relationLoaded('purchaseRequest')) {
            $this->load('purchaseRequest');
        }

        $groupItems = $this->groupItems();
        $totalDomesticCost = $this->domestic_cost ?? 0;

        $method = $this->cost_allocation_method ?? 'value';

        switch ($method) {
            case 'quantity':
                return $this->calculateByQuantity($groupItems, $totalDomesticCost);

            case 'percentage':
                return $this->calculateByPercentage($totalDomesticCost);

            case 'value':
            default:
                return $this->calculateByValue($groupItems, $totalDomesticCost);
        }
    }

    private function calculateByQuantity($groupItems, $totalCost)
    {
        $totalQuantity = $groupItems->sum(function ($item) {
            return $item->purchaseRequest->qty_to_buy ?? ($item->purchaseRequest->required_quantity ?? 0);
        });

        if ($totalQuantity <= 0) {
            return 0;
        }

        $myQuantity = $this->purchaseRequest->qty_to_buy ?? ($this->purchaseRequest->required_quantity ?? 0);
        return ($myQuantity / $totalQuantity) * $totalCost;
    }

    private function calculateByPercentage($totalCost)
    {
        $percentage = $this->allocation_percentage ?? 0;
        return ($percentage / 100) * $totalCost;
    }

    private function calculateByValue($groupItems, $totalCost)
    {
        $totalValue = $groupItems->sum(function ($item) {
            $qty = $item->purchaseRequest->qty_to_buy ?? ($item->purchaseRequest->required_quantity ?? 0);
            $price = $item->purchaseRequest->price_per_unit ?? 0;
            return $qty * $price;
        });

        if ($totalValue <= 0) {
            return 0;
        }

        $myQty = $this->purchaseRequest->qty_to_buy ?? ($this->purchaseRequest->required_quantity ?? 0);
        $myPrice = $this->purchaseRequest->price_per_unit ?? 0;
        $myValue = $myQty * $myPrice;

        return ($myValue / $totalValue) * $totalCost;
    }
}
