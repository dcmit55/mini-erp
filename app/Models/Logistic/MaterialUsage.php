<?php
namespace App\Models\Logistic;

use Illuminate\Database\Eloquent\Model;
use App\Models\Logistic\Inventory;
use App\Models\Production\Project;
use App\Models\Production\JobOrder;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class MaterialUsage extends Model implements AuditableContract
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $auditInclude = [
        'inventory_id', 'project_id', 'job_order_id', 'used_quantity',
    ];

    protected $fillable = ['inventory_id', 'project_id', 'job_order_id', 'used_quantity'];

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
}
