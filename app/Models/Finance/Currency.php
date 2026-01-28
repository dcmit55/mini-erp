<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Logistic\Inventory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Currency extends Model implements AuditableContract
{
    use SoftDeletes, HasFactory, \OwenIt\Auditing\Auditable;

    protected $fillable = ['name', 'exchange_rate'];

    protected $auditInclude = ['name', 'exchange_rate'];

    protected $auditTimestamps = true;

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }
}
