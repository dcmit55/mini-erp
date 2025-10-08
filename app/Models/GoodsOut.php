<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class GoodsOut extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $auditInclude = ['inventory_id', 'project_id', 'quantity', 'remark', 'requested_by', 'material_request_id'];

    protected $auditTimestamps = true;

    protected $table = 'goods_out'; // Pastikan nama tabel sesuai dengan database

    protected $fillable = ['material_request_id', 'inventory_id', 'project_id', 'requested_by', 'department', 'quantity', 'remark'];

    public function materialRequest()
    {
        return $this->belongsTo(MaterialRequest::class, 'material_request_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'requested_by', 'username');
    }

    public function goodsIns()
    {
        return $this->hasMany(GoodsIn::class);
    }

    public function getRemainingQuantityAttribute()
    {
        $totalGoodsIn = $this->goodsIns->sum('quantity');
        return $this->quantity - $totalGoodsIn;
    }
}
