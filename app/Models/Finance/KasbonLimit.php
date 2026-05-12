<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class KasbonLimit extends Model
{
    protected $fillable = [
        'department_id',
        'max_amount',
        'max_tenor',
        'max_active',
        'cooldown_days',
        'is_active',
    ];

    protected $casts = [
        'max_amount'    => 'decimal:2',
        'is_active'     => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(\App\Models\Admin\Department::class);
    }
}
