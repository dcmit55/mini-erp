<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
