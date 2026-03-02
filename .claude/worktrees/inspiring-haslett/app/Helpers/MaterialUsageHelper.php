<?php

namespace App\Helpers;

use App\Models\Logistic\MaterialUsage;
use App\Models\Logistic\GoodsOut;
use App\Models\Logistic\GoodsIn;

class MaterialUsageHelper
{
    /**
     * Sync material usage based on goods_out - goods_in
     * Now supports job_order_id for granular tracking
     *
     * @param int $inventory_id
     * @param int|null $project_id
     * @param string|null $job_order_id Job Order ID (nullable for general project usage)
     */
    public static function sync($inventory_id, $project_id, $job_order_id = null)
    {
        // Pastikan project_id null jika kosong string
        if (empty($project_id)) {
            $project_id = null;
        }

        // Pastikan job_order_id null jika kosong string
        if (empty($job_order_id)) {
            $job_order_id = null;
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
            ->where(function ($q) use ($job_order_id) {
                if ($job_order_id) {
                    $q->where('job_order_id', $job_order_id);
                } else {
                    $q->whereNull('job_order_id');
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
            ->where(function ($q) use ($job_order_id) {
                if ($job_order_id) {
                    $q->where('job_order_id', $job_order_id);
                } else {
                    $q->whereNull('job_order_id');
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
                'job_order_id' => $job_order_id,
            ],
            [
                'used_quantity' => $used,
            ],
        );
    }
}
