<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;
use App\Models\Production\Project;
use App\Models\Production\JobOrder;
use App\Models\Hr\Employee;
use App\Models\Hr\SessionShift;
use App\Helpers\TimeHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Timing extends Model implements AuditableContract
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $auditInclude = ['tanggal', 'job_order_id', 'project_id', 'step', 'parts', 'item', 'employee_id', 'start_time', 'end_time', 'duration_minutes', 'status', 'approval_status', 'approved_by', 'rejection_reason', 'remarks'];

    protected $fillable = [
        'tanggal',
        'job_order_id',
        'project_id',
        'step',
        'parts',
        'item',
        'employee_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'measurement_type',
        'measurement_value',
        'duration_hours',
        'status',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'remarks',
        'department_specific_data',
        'photo',
        // New lifecycle fields
        'session_shift_id',
        'started_at',
        'paused_at',
        'stopped_at',
        'total_paused_minutes',
        'break_deducted_minutes',
        'pause_reason',
        'stop_reason',
        'pause_log',
        'rate_per_hour',
        'session_type',
        'source', // e.g. 'mascot', 'costume', 'animatronics', 'across'
    ];

    protected $casts = [
        'department_specific_data' => 'array',
        'pause_log' => 'array',
        'tanggal' => 'date',
        'measurement_value' => 'decimal:2',
        'duration_hours' => 'decimal:2',
        'duration_minutes' => 'integer',
        'total_paused_minutes' => 'integer',
        'break_deducted_minutes' => 'integer',
        'started_at' => 'datetime',
        'paused_at' => 'datetime',
        'stopped_at' => 'datetime',
        'approved_at' => 'datetime',
        'rate_per_hour' => 'decimal:2',
    ];

    // ============================================
    // ACCESSORS - STANDARDIZED: All based on MINUTES
    // ============================================

    /**
     * Get duration_hours derived from duration_minutes
     * This maintains backward compatibility while using minutes as source of truth
     *
     * @return float
     */
    public function getDurationHoursAttribute()
    {
        $minutes = $this->attributes['duration_minutes'] ?? 0;
        return TimeHelper::minutesToHours($minutes, 2);
    }

    /**
     * Get duration in HH:mm format
     * Examples: "01:30", "00:45", "12:15"
     *
     * @return string
     */
    public function getDurationFormattedAttribute()
    {
        $minutes = $this->attributes['duration_minutes'] ?? 0;
        return TimeHelper::minutesToHHMM($minutes);
    }

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
     * Examples: "1 hour 30 minutes", "45 minutes", "2 hours"
     * STANDARDIZED: Uses TimeHelper
     */
    public function getDurationReadableAttribute()
    {
        $totalMinutes = $this->attributes['duration_minutes'] ?? 0;
        return TimeHelper::minutesToReadable($totalMinutes);
    }

    /**
     * Calculate efficiency: output per hour
     * STANDARDIZED: Uses minutes as base, normalized to hourly rate
     *
     * @return float
     */
    public function getEfficiencyAttribute()
    {
        $minutes = $this->attributes['duration_minutes'] ?? 0;
        $output = $this->measurement_value ?? 0;

        return TimeHelper::calculateEfficiency($output, $minutes);
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

    // ============================================
    // APPROVAL SCOPES
    // ============================================

    /**
     * Scope: Only pending approval timings
     */
    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    /**
     * Scope: Only approved timings
     */
    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    /**
     * Scope: Only rejected timings
     */
    public function scopeRejected($query)
    {
        return $query->where('approval_status', 'rejected');
    }

    /**
     * Scope: Only paused sessions
     */
    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    /**
     * Scope: Only frozen sessions (timer stopped, still in monitor, NOT in approval)
     */
    public function scopeFrozen($query)
    {
        return $query->where('status', 'frozen');
    }

    /**
     * Check if timing is frozen
     */
    public function isFrozen()
    {
        return $this->status === 'frozen';
    }

    // ============================================
    // APPROVAL METHODS
    // ============================================

    /**
     * Approve this timing session
     *
     * Snaps a rate_per_hour from the employee's CURRENT salary at approval time.
     * This locks the historical labor cost — future salary changes will NOT
     * retroactively alter already-approved costing data.
     *
     * @param int $userId User ID who approved
     * @return bool
     */
    public function approve($userId)
    {
        $this->approval_status = 'approved';
        $this->approved_by = $userId;
        $this->approved_at = now();
        $this->rejection_reason = null;

        // Snapshot rate_per_hour if not already set
        if (is_null($this->rate_per_hour)) {
            // Load employee if not already loaded
            $employee = $this->relationLoaded('employee') ? $this->employee : $this->employee()->first();
            $salary = (float) ($employee->salary ?? 0);
            if ($salary > 0) {
                $this->rate_per_hour = round($salary / 173, 2);
            }
        }

        return $this->save();
    }

    /**
     * Reject this timing session
     *
     * @param int $userId User ID who rejected
     * @param string|null $reason Reason for rejection
     * @return bool
     */
    public function reject($userId, $reason = null)
    {
        $this->approval_status = 'rejected';
        $this->approved_by = $userId;
        $this->approved_at = now();
        $this->rejection_reason = $reason;

        return $this->save();
    }

    /**
     * Check if timing is pending approval
     *
     * @return bool
     */
    public function isPending()
    {
        return $this->approval_status === 'pending';
    }

    /**
     * Check if timing is paused
     */
    public function isPaused()
    {
        return $this->status === 'paused';
    }

    /**
     * Check if timing is approved
     *
     * @return bool
     */
    public function isApproved()
    {
        return $this->approval_status === 'approved';
    }

    /**
     * Check if timing is rejected
     *
     * @return bool
     */
    public function isRejected()
    {
        return $this->approval_status === 'rejected';
    }

    /**
     * Relationship: User who approved/rejected
     */
    public function approver()
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'approved_by');
    }

    public function sessionShift()
    {
        return $this->belongsTo(SessionShift::class, 'session_shift_id');
    }

    // ============================================
    // STATUS HELPERS (new lifecycle fields)
    // ============================================

    /** Currently running (not paused, not stopped) */
    public function isRunning(): bool
    {
        return in_array($this->status, ['on progress', 'running']) && is_null($this->end_time);
    }

    /** Paused (auto-break or manual) — still open, not yet stopped */
    public function isCurrentlyPaused(): bool
    {
        return in_array($this->status, ['paused', 'frozen']) && is_null($this->end_time);
    }

    /** Fully stopped / complete */
    public function isStopped(): bool
    {
        return in_array($this->status, ['complete', 'stopped']) || !is_null($this->end_time);
    }

    /** Auto-paused by the break scheduler (has auto_break_paused marker) */
    public function isAutoBreakPaused(): bool
    {
        return $this->isCurrentlyPaused() && !empty(($this->department_specific_data ?? [])['auto_break_paused']);
    }

    /**
     * Net active minutes = total elapsed minutes minus all paused time.
     * Uses started_at / stopped_at when available, falls back to start_time / end_time.
     */
    public function getNetActiveMinutesAttribute(): int
    {
        $start = $this->started_at ?? \Carbon\Carbon::parse($this->tanggal->format('Y-m-d') . ' ' . $this->start_time);
        $end = $this->stopped_at ?? ($this->end_time ? \Carbon\Carbon::parse($this->tanggal->format('Y-m-d') . ' ' . $this->end_time) : now());

        $gross = max(0, $start->diffInMinutes($end));
        $paused = $this->total_paused_minutes ?? 0;

        return max(0, $gross - $paused);
    }

    // ── Additional scopes ─────────────────────────────────────────────────────

    /** Active sessions: running OR auto-break-frozen (no end_time) */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['on progress', 'running', 'frozen', 'paused'])->whereNull('end_time');
    }

    /** Scope: only stopped/complete sessions */
    public function scopeStopped($query)
    {
        return $query->whereIn('status', ['complete', 'stopped']);
    }
}
