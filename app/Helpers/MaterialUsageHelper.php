<?php
// app/Helpers/MaterialUsageHelper.php
namespace App\Helpers;

use App\Models\MaterialUsage;
use App\Models\GoodsOut;
use App\Models\GoodsIn;

class MaterialUsageHelper
{
    public static function sync($inventory_id, $project_id)
    {
        // Pastikan project_id null jika kosong string
        if (empty($project_id)) {
            $project_id = null;
        }

        // Hitung total Goods Out (hanya yang tidak dihapus)
        $goodsOutTotal = GoodsOut::where('inventory_id', $inventory_id)
            ->where(function ($q) use ($project_id) {
                if ($project_id) {
                    $q->where('project_id', $project_id);
                } else {
                    $q->whereNull('project_id');
                }
            })
            ->whereNull('deleted_at')
            ->sum('quantity');

        // Hitung total Goods In
        $goodsInTotal = GoodsIn::where('inventory_id', $inventory_id)
            ->where(function ($q) use ($project_id) {
                if ($project_id) {
                    $q->where('project_id', $project_id);
                } else {
                    $q->whereNull('project_id');
                }
            })
            ->sum('quantity');

        // Hitung used_quantity
        $used = $goodsOutTotal - $goodsInTotal;

        // Perbarui Material Usage
        MaterialUsage::updateOrCreate(
            [
                'inventory_id' => $inventory_id,
                'project_id' => $project_id,
            ],
            [
                'used_quantity' => $used,
            ],
        );
    }
}
