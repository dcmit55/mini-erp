<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use App\Models\Hr\Employee;
use App\Models\Admin\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'date', 'status', 'recorded_time', 'recorded_by', 'notes', 'late_time'];

    protected $casts = [
        'date' => 'date',
        'recorded_time' => 'datetime:H:i:s',
    ];

    // Relationship ke Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Relationship ke User (HR yang record)
    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // Scope untuk filter by date
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    // Scope untuk filter by status
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
