<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class ExternalRequest extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'material_name', 'inventory_id', 'required_quantity', 'unit', 'stock_level', 'project_id', 'requested_by'];

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
        return $this->belongsTo(User::class, 'requested_by');
    }
}
