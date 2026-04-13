<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class WarningLetterAcknowledgment extends Model
{
    protected $fillable = [
        'warning_letter_id', 'employee_id',
        'acknowledged_at', 'method',
        'signature_path', 'witness_id',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    public function warningLetter()
    {
        return $this->belongsTo(WarningLetter::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function witness()
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'witness_id');
    }
}
