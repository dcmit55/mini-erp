<?php

namespace App\Models\Logistic;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Logistic\Inventory;
use App\Models\Logistic\GoodsOut;
use App\Models\Production\Project;
use App\Models\Admin\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class MaterialRequest extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $auditInclude = ['inventory_id', 'project_id', 'quantity', 'processed_qty', 'status', 'remark', 'requested_by'];

    protected $auditTimestamps = true;

    protected $fillable = ['inventory_id', 'project_id', 'job_order_id', 'qty', 'processed_qty', 'requested_by', 'status', 'remark', 'approved_at'];

    protected $casts = [
        'created_at' => 'datetime', // Pastikan created_at di-cast sebagai datetime
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
        return $this->belongsTo(\App\Models\Production\JobOrder::class, 'job_order_id', 'id');
    }

    public function goodsOuts()
    {
        return $this->hasMany(GoodsOut::class);
    }

    public function getStatusBadgeClass()
    {
        return match ($this->status) {
            'pending' => 'text-bg-warning',
            'approved' => 'text-bg-primary',
            'delivered' => 'text-bg-success',
            'canceled' => 'text-bg-danger',
            default => '',
        };
    }

    public function getRemainingQtyAttribute()
    {
        return round($this->qty - $this->processed_qty, 2);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'requested_by', 'username');
    }

    public function getDepartmentNameAttribute()
    {
        return $this->user && $this->user->department ? $this->user->department->name : null;
    }
}
