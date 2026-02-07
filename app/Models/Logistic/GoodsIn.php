<?php

namespace App\Models\Logistic;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Logistic\Inventory;
use App\Models\Logistic\GoodsOut;
use App\Models\Production\Project;
use App\Models\Production\JobOrder;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class GoodsIn extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $auditInclude = ['inventory_id', 'project_id', 'job_order_id', 'quantity', 'remark', 'goods_out_id'];

    protected $auditTimestamps = true;

    protected $table = 'goods_in'; // Pastikan nama tabel sesuai dengan database

    protected $fillable = ['goods_out_id', 'inventory_id', 'project_id', 'job_order_id', 'quantity', 'returned_by', 'returned_at', 'remark'];

    protected $casts = [
        'returned_at' => 'datetime',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class, 'job_order_id', 'id');
    }

    public function goodsOut()
    {
        return $this->belongsTo(GoodsOut::class);
    }
}
