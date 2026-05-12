<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class EmployeePayrollFlag extends Model
{
    protected $fillable = [
        'employee_id', 'type', 'warning_letter_id',
        'notes', 'is_resolved', 'resolved_at', 'resolved_by', 'created_by',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function warningLetter()
    {
        return $this->belongsTo(WarningLetter::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'created_by');
    }

    public function resolver()
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'resolved_by');
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }
}
