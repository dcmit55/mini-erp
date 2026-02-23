<?php
// app/Models/Hr/OvertimeRequest.php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class OvertimeRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uid', 'employee_id', 'department_id', 'job_order_id',
        'reason', 'ot_code', 'start_time', 'end_time',
        'total_hours', 'break_deduction', 'net_hours',
        'hr_approval_status', 'hr_approved_by', 'hr_approved_at',
        'director_approval_status', 'director_approved_by', 'director_approved_at',
        'status', 'is_passed'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'hr_approved_at' => 'datetime',
        'director_approved_at' => 'datetime',
        'total_hours' => 'decimal:2',
        'break_deduction' => 'decimal:2',
        'net_hours' => 'decimal:2',
        'is_passed' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(\App\Models\Hr\Employee::class);
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\Admin\Department::class);
    }

    public function jobOrder()
    {
        return $this->belongsTo(\App\Models\Production\JobOrder::class);
    }

    public function hrApprover()
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'hr_approved_by');
    }

    public function directorApprover()
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'director_approved_by');
    }

    public function payDetail()
    {
        return $this->hasOne(\App\Models\Hr\OvertimePayDetail::class, 'overtime_request_id');
    }

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

    public function calculateAndSavePayDetail()
    {
        if ($this->payDetail()->exists()) {
            return false;
        }

        $employee = $this->employee;
        $monthlySalary = $employee->salary ?? 0;
        if ($monthlySalary <= 0) {
            throw new \Exception("Karyawan {$employee->name} tidak memiliki salary.");
        }

        $hourlyRate = $monthlySalary / 173;
        $netHours = $this->net_hours;
        $otCode = $this->ot_code;
        $breakdown = [];
        $totalPay = 0;

        if ($otCode === 'Normal Day') {
            if ($netHours <= 1) {
                $totalPay = $netHours * $hourlyRate * 1.5;
                $breakdown[] = ['segment' => 'Jam pertama', 'hours' => $netHours, 'rate' => 1.5, 'amount' => $totalPay];
            } else {
                $firstHour = 1 * $hourlyRate * 1.5;
                $remainingHours = $netHours - 1;
                $remainingPay = $remainingHours * $hourlyRate * 2;
                $totalPay = $firstHour + $remainingPay;
                $breakdown = [
                    ['segment' => 'Jam pertama', 'hours' => 1, 'rate' => 1.5, 'amount' => $firstHour],
                    ['segment' => 'Jam selanjutnya', 'hours' => $remainingHours, 'rate' => 2, 'amount' => $remainingPay],
                ];
            }
        } else {
            if ($netHours <= 7) {
                $totalPay = $netHours * $hourlyRate * 2;
                $breakdown[] = ['segment' => '7 jam pertama', 'hours' => $netHours, 'rate' => 2, 'amount' => $totalPay];
            } elseif ($netHours <= 8) {
                $first7 = 7 * $hourlyRate * 2;
                $eighthHour = ($netHours - 7) * $hourlyRate * 3;
                $totalPay = $first7 + $eighthHour;
                $breakdown = [
                    ['segment' => '7 jam pertama', 'hours' => 7, 'rate' => 2, 'amount' => $first7],
                    ['segment' => 'Jam ke-8', 'hours' => $netHours - 7, 'rate' => 3, 'amount' => $eighthHour],
                ];
            } else {
                $first7 = 7 * $hourlyRate * 2;
                $eighthHour = 1 * $hourlyRate * 3;
                $remaining = ($netHours - 8) * $hourlyRate * 4;
                $totalPay = $first7 + $eighthHour + $remaining;
                $breakdown = [
                    ['segment' => '7 jam pertama', 'hours' => 7, 'rate' => 2, 'amount' => $first7],
                    ['segment' => 'Jam ke-8', 'hours' => 1, 'rate' => 3, 'amount' => $eighthHour],
                    ['segment' => 'Jam ke-9 dst', 'hours' => $netHours - 8, 'rate' => 4, 'amount' => $remaining],
                ];
            }
        }

        $payDetail = new \App\Models\Hr\OvertimePayDetail([
            'uid' => (string) Str::uuid(),
            'overtime_request_id' => $this->id,
            'employee_id' => $this->employee_id,
            'ot_code' => $otCode,
            'net_hours' => $netHours,
            'hourly_rate' => $hourlyRate,
            'total_pay' => $totalPay,
            'breakdown' => $breakdown,
            'calculated_at' => now(),
        ]);
        $payDetail->save();

        return true;
    }

    public function getProjectAttribute()
    {
        return $this->jobOrder?->name;
    }

    public function isHrApproved()
    {
        return $this->hr_approval_status === 'approved';
    }

    public function isDirectorApproved()
    {
        return $this->director_approval_status === 'approved';
    }

    public function isFullyApproved()
    {
        return $this->isHrApproved() && $this->isDirectorApproved();
    }

    public function updateOverallStatus()
    {
        if ($this->hr_approval_status === 'rejected' || $this->director_approval_status === 'rejected') {
            $this->status = 'rejected';
        } elseif ($this->isFullyApproved()) {
            $this->status = 'approved';
        } elseif ($this->hr_approval_status === 'approved' || $this->director_approval_status === 'approved') {
            $this->status = 'submitted';
        } else {
            $this->status = 'draft';
        }
        $this->saveQuietly();
    }
}