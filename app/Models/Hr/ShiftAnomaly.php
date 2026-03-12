<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class ShiftAnomaly extends Model
{
    protected $table = 'shift_anomalies';

    protected $fillable = [
        'employee_id', 'anomaly_date', 'anomaly_type', 'severity',
        'context', 'resolution_status', 'resolution_note',
        'resolved_by', 'resolved_at',
    ];

    protected $casts = [
        'anomaly_date' => 'date',
        'context'      => 'array',
        'resolved_at'  => 'datetime',
    ];

    // Severity mapping berdasarkan tipe anomali
    public static array $severityMap = [
        'SHORT_HOURS'   => 'LOW',
        'NO_BREAKS'     => 'LOW',
        'LONG_ABSENCE'  => 'MEDIUM',
        'EARLY_LEAVE'   => 'MEDIUM',
        'PATTERN'       => 'HIGH',
        'EARLY_CHECKIN' => 'MEDIUM',
        'MISSING_OUT'   => 'HIGH',
        'DUPLICATE_TAP' => 'LOW',
    ];

    public static function log(int $employeeId, string $date, string $type, array $context = []): self
    {
        return self::create([
            'employee_id'  => $employeeId,
            'anomaly_date' => $date,
            'anomaly_type' => $type,
            'severity'     => self::$severityMap[$type] ?? 'LOW',
            'context'      => $context,
        ]);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function resolver()
    {
        return $this->belongsTo(\App\Models\User::class, 'resolved_by');
    }
}
