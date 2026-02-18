<?php

namespace App\Models\Logistic;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\User;
use App\Models\Logistic\Inventory;
use App\Models\Logistic\MaterialRequest;
use App\Models\Logistic\GoodsIn;
use App\Models\Production\Project;
use App\Models\Production\JobOrder;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class GoodsOut extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $auditInclude = ['inventory_id', 'project_id', 'job_order_id', 'quantity', 'remark', 'requested_by', 'material_request_id'];

    protected $auditTimestamps = true;

    protected $table = 'goods_out'; // Pastikan nama tabel sesuai dengan database

    protected $fillable = ['material_request_id', 'inventory_id', 'project_id', 'job_order_id', 'requested_by', 'department', 'quantity', 'remark'];

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

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class, 'job_order_id', 'id');
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

    public function canBeDeleted()
    {
        $authUser = auth()->user();

        // Super admin can delete anything
        if ($authUser->isSuperAdmin()) {
            return true;
        }

        // If goods out has related goods in, cannot be deleted
        if ($this->goodsIns()->exists()) {
            return false;
        }

        // If goods out comes from material request, only super admin can delete
        if ($this->material_request_id) {
            return false;
        }

        // Independent goods out can be deleted by logistic admin
        return $authUser->isLogisticAdmin();
    }

    public function getDeleteTooltip()
    {
        $authUser = auth()->user();

        if ($this->goodsIns()->exists()) {
            return 'Cannot delete - has related Goods In';
        }

        if ($this->material_request_id && !$authUser->isSuperAdmin()) {
            return 'Cannot delete - from Material Request (Super Admin only)';
        }

        if ($this->material_request_id && $authUser->isSuperAdmin()) {
            return 'Delete (Super Admin - from Material Request)';
        }

        return 'Delete';
    }
}
