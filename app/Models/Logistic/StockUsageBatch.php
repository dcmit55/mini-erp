<?php

namespace App\Models\Logistic;

use Illuminate\Database\Eloquent\Model;

class StockUsageBatch extends Model
{
    protected $fillable = [
        'goods_out_id',
        'batch_id',
        'qty_used',
    ];

    protected $casts = [
        'qty_used' => 'float',
    ];

    public function goodsOut()
    {
        return $this->belongsTo(GoodsOut::class, 'goods_out_id');
    }

    public function batch()
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }
}
