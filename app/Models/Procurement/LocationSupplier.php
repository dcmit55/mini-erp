<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Procurement\Supplier;

class LocationSupplier extends Model
{
    use HasFactory;

    protected $table = 'location_supplier';

    protected $fillable = ['name'];

    public function suppliers()
    {
        return $this->hasMany(Supplier::class, 'location_id');
    }
}
