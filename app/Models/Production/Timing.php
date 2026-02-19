<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;
use App\Models\Production\Project;
use App\Models\Production\JobOrder;
use App\Models\Hr\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Timing extends Model
{
    use HasFactory;

    protected $fillable = ['tanggal', 'job_order_id', 'project_id', 'step', 'parts', 'employee_id', 'start_time', 'end_time', 'measurement_type', 'measurement_value', 'duration_hours', 'status', 'remarks', 'department_specific_data', 'photo'];

    /**
     * Cast department_specific_data as array for easy access
     */
    protected $casts = [
        'department_specific_data' => 'array',
        'tanggal' => 'date',
        'measurement_value' => 'decimal:2',
        'duration_hours' => 'decimal:2',
    ];

    // ============================================
    // ACCESSORS
    // ============================================

    /**
     * Get duration between start_time and end_time (or now if still running)
     * Returns formatted string HH:MM:SS
     */
    public function getDurationAttribute()
    {
        if (!$this->start_time) {
            return '00:00:00';
        }

        try {
            $today = now()->format('Y-m-d');
            $start = \Carbon\Carbon::parse($today . ' ' . $this->start_time);
            $end = $this->end_time ? \Carbon\Carbon::parse($today . ' ' . $this->end_time) : now();

            $diff = $start->diff($end);
            return sprintf('%02d:%02d:%02d', $diff->h, $diff->i, $diff->s);
        } catch (\Exception $e) {
            return '00:00:00';
        }
    }

    /**
     * Get duration in hours (decimal format for calculations)
     * NOTE: Ini baca dari kolom duration_hours di database, bukan calculated
     */
    public function getDurationHoursDecimalAttribute()
    {
        return $this->attributes['duration_hours'] ?? 0;
    }

    /**
     * Get duration in human-readable format
     * Examples: "1 jam 30 menit", "45 menit", "2 jam"
     */
    public function getDurationReadableAttribute()
    {
        $hours = $this->attributes['duration_hours'] ?? 0;

        if ($hours == 0) {
            return '0 menit';
        }

        $totalMinutes = round($hours * 60);
        $h = floor($totalMinutes / 60);
        $m = $totalMinutes % 60;

        $parts = [];
        if ($h > 0) {
            $parts[] = $h . ' jam';
        }
        if ($m > 0) {
            $parts[] = $m . ' menit';
        }

        return implode(' ', $parts);
    }

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class, 'job_order_id', 'id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // ============================================
    // QUERY SCOPES - For Efficient Reusable Queries
    // ============================================

    /**
     * Scope: Filter by project
     */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope: Filter by job order
     */
    public function scopeForJobOrder($query, $jobOrderId)
    {
        return $query->where('job_order_id', $jobOrderId);
    }

    /**
     * Scope: Filter by employee
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope: Only completed timings
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'complete')->whereNotNull('end_time');
    }

    /**
     * Scope: Only running/active timings
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'on progress')->whereNull('end_time');
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal', [$startDate, $endDate]);
    }

    /**
     * Scope: Filter by today's date
     */
    public function scopeToday($query)
    {
        return $query->whereDate('tanggal', today());
    }

    /**
     * Scope: Eager load all relationships
     */
    public function scopeWithRelations($query)
    {
        return $query->with(['employee.department', 'project', 'jobOrder.department']);
    }
}
