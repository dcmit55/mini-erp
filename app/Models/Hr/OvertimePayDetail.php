<?php
// app/Models/Hr/OvertimePayDetail.php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimePayDetail extends Model
{
    use HasFactory;

    protected $table = 'overtime_pay_details';

    protected $fillable = [
        'uid',
        'overtime_request_id',
        'employee_id',
        'ot_code',
        'net_hours',
        'hourly_rate',
        'total_pay',
        'breakdown',
        'calculated_at',
    ];

    protected $casts = [
        'breakdown' => 'array',
        'calculated_at' => 'datetime',
        'net_hours' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'total_pay' => 'decimal:2',
    ];

    public function overtimeRequest()
    {
        return $this->belongsTo(OvertimeRequest::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Accessor untuk menampilkan net hours dalam format jam dan menit
     */
    public function getNetHoursFormattedAttribute()
    {
        $hours = floor($this->net_hours);
        $minutes = round(($this->net_hours - $hours) * 60);
        
        if ($minutes == 60) {
            $hours += 1;
            $minutes = 0;
        }
        
        return $hours . ' jam' . ($minutes > 0 ? ' ' . $minutes . ' menit' : '');
    }
}