<?php

namespace App\Models\Logistic;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Logistic\GoodsMovement;
use App\Models\Procurement\GoodsReceive;
use App\Models\Procurement\GoodsReceiveDetail;
use App\Models\Production\Project;
use App\Models\Logistic\Inventory;
use App\Models\Admin\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsMovementItem extends Model
{
    protected $fillable = [
        'goods_movement_id',
        'material_type',
        'project_id',
        'goods_receive_id',
        'goods_receive_detail_id',
        'inventory_id',
        'new_material_name',
        'quantity',
        'unit',
        'notes',
        'transferred_to_inventory',
        'transferred_at',
        'transferred_by',
    ];
    protected $casts = [
        'transferred_to_inventory' => 'boolean',
        'transferred_at' => 'datetime',
    ];

    public function goodsMovement()
    {
        return $this->belongsTo(GoodsMovement::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function goodsReceive()
    {
        return $this->belongsTo(GoodsReceive::class);
    }

    public function goodsReceiveDetail()
    {
        return $this->belongsTo(GoodsReceiveDetail::class);
    }

    public function transferredByUser()
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
}
