<?php
namespace App\Models\Logistic;

use Illuminate\Database\Eloquent\Model;
use App\Models\Logistic\Inventory;
use App\Models\Production\Project;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialUsage extends Model
{
    use SoftDeletes;

    protected $fillable = ['inventory_id', 'project_id', 'used_quantity'];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
