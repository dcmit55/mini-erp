<?php

namespace App\Services;

use App\Models\Production\Timing;
use App\Models\Production\JobOrder;
use App\Models\Hr\Employee;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Employee Performance Service
 *
 * Calculate employee performance based on:
 * - duration_minutes (actual work time)
 * - measurement_value (output produced)
 * - measurement_type (progress % or quantity)
 * - job_order standard configuration
 */
class EmployeePerformanceService
{
    /**
     * Minimum working minutes required for ranking (configurable)
     * Employees below this threshold are excluded from rankings
     * Set to 0 to show all employees with any duration data
     */
    protected int $minimumWorkingMinutes;

    /**
     * Default standard time per unit (in minutes) for 'qty' measurement type
     * IMPORTANT: This should be set per job_order for accurate productivity tracking
     * Default: 1.0 minute per unit (conservative estimate)
     */
    protected float $defaultStandardTimePerUnit;

    public function __construct(int $minimumWorkingMinutes = 0, float $defaultStandardTimePerUnit = 1.0)
    {
        $this->minimumWorkingMinutes = $minimumWorkingMinutes;
        $this->defaultStandardTimePerUnit = $defaultStandardTimePerUnit;
    }

    /**
     * Calculate standard minutes earned for a single timing record
     *
     * FORMULA:
     * - For 'progress' type: (progress_percentage / 100) × job_order.total_standard_minutes
     * - For 'qty/pcs/unit' type: quantity × job_order.standard_time_per_unit
     *
     * PRODUCTIVITY = (standard_minutes_earned / actual_duration_minutes) × 100%
     *
     * Example:
     * - Job requires 100 units, standard time = 2 min/unit = 200 min total
     * - Employee produces 50 units in 120 minutes
     * - Standard earned = 50 × 2 = 100 minutes
     * - Productivity = (100 / 120) × 100 = 83.33%
     *
     * @param Timing $timing
     * @return float
     */
    public function calculateStandardMinutesEarned(Timing $timing): float
    {
        $outputValue = $timing->measurement_value ?? 0;
        $measurementType = $timing->measurement_type;

        if ($outputValue <= 0) {
            return 0.0;
        }

        // Handle percentage-based progress tracking
        if ($measurementType === 'progress' || $measurementType === 'percentage') {
            return $this->calculateStandardMinutesForPercentage($timing, $outputValue);
        }

        // Handle quantity-based tracking (qty, pcs, unit, piece, item, set, meter, cm, kg, gram)
        return $this->calculateStandardMinutesForQuantity($timing, $outputValue);
    }

    /**
     * Calculate standard minutes for percentage-based measurement
     * Uses job_order.total_standard_minutes
     *
     * @param Timing $timing
     * @param float $outputValue
     * @return float
     */
    protected function calculateStandardMinutesForPercentage(Timing $timing, float $outputValue): float
    {
        if (!$timing->jobOrder) {
            return 0.0;
        }

        // Use job_order's total_standard_minutes if available
        if ($timing->jobOrder->total_standard_minutes && $timing->jobOrder->total_standard_minutes > 0) {
            return ($outputValue / 100) * $timing->jobOrder->total_standard_minutes;
        }

        // Fallback: calculate from estimated duration
        $jobOrderStandardMinutes = $this->getJobOrderStandardMinutes($timing->jobOrder);
        return ($outputValue / 100) * $jobOrderStandardMinutes;
    }

    /**
     * Calculate standard minutes for quantity-based measurement
     * Uses job_order.standard_time_per_unit or calculates from actual performance
     *
     * IMPORTANT: If job_order doesn't have standard_time_per_unit configured,
     * we calculate it from actual duration to avoid unrealistic productivity scores
     *
     * @param Timing $timing
     * @param float $outputValue
     * @return float
     */
    protected function calculateStandardMinutesForQuantity(Timing $timing, float $outputValue): float
    {
        // Priority 1: Use job_order's standard_time_per_unit if configured
        if ($timing->jobOrder && $timing->jobOrder->standard_time_per_unit > 0) {
            return $outputValue * $timing->jobOrder->standard_time_per_unit;
        }

        // Priority 2: Calculate standard from actual performance (prevents extreme values)
        // This assumes current performance is approximately standard (100% productivity)
        // Standard minutes = actual minutes (makes productivity close to 100%)
        if ($timing->duration_minutes > 0 && $outputValue > 0) {
            $actualTimePerUnit = $timing->duration_minutes / $outputValue;
            return $outputValue * $actualTimePerUnit;
        }

        // Fallback: Use conservative default (1 minute per unit)
        return $outputValue * $this->defaultStandardTimePerUnit;
    }

    /**
     * Get job order's total standard minutes
     *
     * @param JobOrder $jobOrder
     * @return float
     */
    protected function getJobOrderStandardMinutes(JobOrder $jobOrder): float
    {
        // TODO: Implement this when job_orders has total_standard_minutes column
        // For now, calculate from date range or use a default

        if ($jobOrder->start_date && $jobOrder->end_date) {
            $start = Carbon::parse($jobOrder->start_date);
            $end = Carbon::parse($jobOrder->end_date);
            $workingDays = $start->diffInWeekdays($end);

            // Assume 8 working hours per day = 480 minutes
            return $workingDays * 480;
        }

        // Default: 1 week = 5 days * 8 hours * 60 minutes
        return 2400;
    }

    /**
     * Get standard time per unit (in minutes) from job_order
     *
     * @param Timing $timing
     * @return float
     */
    protected function getStandardTimePerUnit(Timing $timing): float
    {
        // Use job_order.standard_time_per_unit or default
        if ($timing->jobOrder && $timing->jobOrder->standard_time_per_unit > 0) {
            return (float) $timing->jobOrder->standard_time_per_unit;
        }

        return $this->defaultStandardTimePerUnit;
    }

    /**
     * PART 1 & 4: Calculate productivity score for a single employee
     *
     * Formula: productivity_score = MIN(100, SUM(standard_minutes_earned) / SUM(duration_minutes) * 100)
     *
     * Explanation:
     * - Standard minutes earned = Quantity × Standard Time Per Unit (or Progress % × Total Standard Minutes)
     * - Productivity = (Standard minutes / Actual minutes) × 100%
     * - Capped at 100% maximum (excellent performance cannot exceed 100%)
     *
     * Example:
     * - Standard time per unit: 10 minutes
     * - Worker completes 4 units in 2 minutes: (4 × 10) / 2 = 200% → Capped to 100%
     * - Worker completes 3 units in 5 minutes: (3 × 10) / 5 = 60%
     * - Worker completes 1 unit in 5 minutes: (1 × 10) / 5 = 20%
     *
     * @param int $employeeId
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param int|null $departmentId
     * @param int|string|null $jobOrderId Filter by specific job order
     * @return float Productivity as percentage (0-100%)
     */
    public function calculateEmployeeProductivityScore(int $employeeId, ?Carbon $startDate = null, ?Carbon $endDate = null, ?int $departmentId = null, $jobOrderId = null): float
    {
        $query = Timing::query()->where('employee_id', $employeeId)->where('duration_minutes', '>', 0)->whereNotNull('measurement_value')->where('measurement_value', '>', 0); // Only records with valid measurement

        // Apply date filters
        if ($startDate) {
            $query->where('tanggal', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('tanggal', '<=', $endDate);
        }

        // PART 1: Filter by Job Order (Project)
        if ($jobOrderId) {
            $query->where('job_order_id', $jobOrderId);
        }

        // Apply department filter via employee relationship
        if ($departmentId) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $timings = $query->with(['jobOrder', 'employee'])->get();

        if ($timings->isEmpty()) {
            return 0.0;
        }

        $totalStandardMinutes = 0;
        $totalActualMinutes = 0;

        foreach ($timings as $timing) {
            $totalStandardMinutes += $this->calculateStandardMinutesEarned($timing);
            $totalActualMinutes += $timing->duration_minutes;
        }

        // PART 3: Prevent division by zero
        if ($totalActualMinutes == 0) {
            return 0.0;
        }

        // PART 3: Calculate productivity as percentage and cap at 100%
        $rawScore = ($totalStandardMinutes / $totalActualMinutes) * 100;
        return round(min($rawScore, 100.0), 2);
    }

    /**
     * PART 1 & 5: Get employee productivity ranking with filters
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param int|null $departmentId
     * @param int|string|null $jobOrderId Filter by specific job order
     * @param int|null $employeeId Filter by specific employee
     * @param int $minWorkingMinutes Minimum minutes to qualify for ranking
     * @return \Illuminate\Support\Collection
     */
    public function getEmployeeRanking(?Carbon $startDate = null, ?Carbon $endDate = null, ?int $departmentId = null, $jobOrderId = null, ?int $employeeId = null, ?int $minWorkingMinutes = null)
    {
        // PART 3: Exclude employees below minimum working minutes threshold
        $minWorkingMinutes = $minWorkingMinutes ?? $this->minimumWorkingMinutes;

        // PART 5: Use SQL aggregation for performance
        $query = DB::table('timings')
            ->select(['timings.employee_id', 'employees.name as employee_name', 'employees.department_id', 'departments.name as department_name', DB::raw('SUM(timings.duration_minutes) as total_work_minutes'), DB::raw('COUNT(timings.id) as total_sessions')])
            ->join('employees', 'timings.employee_id', '=', 'employees.id')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
            ->where('timings.duration_minutes', '>', 0)
            ->whereNotNull('timings.measurement_value')
            ->where('timings.measurement_value', '>', 0)
            ->groupBy('timings.employee_id', 'employees.name', 'employees.department_id', 'departments.name');

        // Apply filters
        if ($startDate) {
            $query->where('timings.tanggal', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('timings.tanggal', '<=', $endDate);
        }
        if ($departmentId) {
            $query->where('employees.department_id', $departmentId);
        }

        // PART 1: Filter by Job Order
        if ($jobOrderId) {
            $query->where('timings.job_order_id', $jobOrderId);
        }

        // PART 1: Filter by Employee
        if ($employeeId) {
            $query->where('timings.employee_id', $employeeId);
        }

        // PART 3: Filter by minimum working minutes (optional - set to 0 to show all)
        if ($minWorkingMinutes > 0) {
            $query->havingRaw('SUM(timings.duration_minutes) >= ?', [$minWorkingMinutes]);
        }

        $employeeSummaries = $query->get();

        // OPTIMIZATION: Load all timings once with eager loading to prevent N+1
        $employeeIds = $employeeSummaries->pluck('employee_id')->toArray();

        $timingsQuery = Timing::query()->whereIn('employee_id', $employeeIds)->where('duration_minutes', '>', 0)->whereNotNull('measurement_value')->where('measurement_value', '>', 0)->with('jobOrder'); // Eager load jobOrder to prevent N+1

        if ($startDate) {
            $timingsQuery->where('tanggal', '>=', $startDate);
        }
        if ($endDate) {
            $timingsQuery->where('tanggal', '<=', $endDate);
        }
        if ($jobOrderId) {
            $timingsQuery->where('job_order_id', $jobOrderId);
        }

        $allTimings = $timingsQuery->get()->groupBy('employee_id');

        // Calculate productivity for each employee
        $rankings = $employeeSummaries->map(function ($summary) use ($allTimings) {
            $employeeTimings = $allTimings->get($summary->employee_id, collect());

            $totalStandardMinutes = 0;
            $totalActualMinutes = 0;

            foreach ($employeeTimings as $timing) {
                $totalStandardMinutes += $this->calculateStandardMinutesEarned($timing);
                $totalActualMinutes += $timing->duration_minutes;
            }

            // Calculate productivity score
            // Formula: (standard_minutes / actual_minutes) × 100%, capped at 100%
            // Excellent performance: 80-100%
            // Good performance: 60-80%
            // Normal performance: 40-60%
            // Poor performance: Below 40%
            $productivityScore = 0.0;
            if ($totalActualMinutes > 0) {
                $rawScore = ($totalStandardMinutes / $totalActualMinutes) * 100;

                // Cap at 100% to ensure maximum productivity is 100%
                $productivityScore = min($rawScore, 100.0);
                $productivityScore = round($productivityScore, 2);
            }

            return (object) [
                'employee_id' => $summary->employee_id,
                'employee_name' => $summary->employee_name,
                'department_name' => $summary->department_name ?? 'N/A',
                'total_working_minutes' => $summary->total_work_minutes,
                'total_sessions' => $summary->total_sessions,
                'total_standard_minutes' => round($totalStandardMinutes, 2),
                'productivity_score' => $productivityScore,
                'rank' => 0, // Will be assigned below
            ];
        });

        // PART 3: Sort by productivity (highest first) and assign DENSE_RANK
        $rankings = $rankings->sortByDesc('productivity_score')->values();

        $currentRank = 1;
        $previousScore = null;

        foreach ($rankings as $index => $ranking) {
            // PART 3: Dense ranking - same score = same rank, no gaps
            if ($previousScore !== null && $ranking->productivity_score < $previousScore) {
                $currentRank = $index + 1;
            }

            $ranking->rank = $currentRank;
            $previousScore = $ranking->productivity_score;
        }

        return $rankings;
    }

    /**
     * Get detailed productivity report for a specific employee
     *
     * @param int $employeeId
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function getEmployeeProductivityReport(int $employeeId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = Timing::query()
            ->where('employee_id', $employeeId)
            ->where('duration_minutes', '>', 0)
            ->whereNotNull('measurement_value')
            ->where('measurement_value', '>', 0)
            ->with(['jobOrder', 'project']);

        if ($startDate) {
            $query->where('tanggal', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('tanggal', '<=', $endDate);
        }

        $timings = $query->get();

        $totalActualMinutes = 0;
        $totalStandardMinutes = 0;
        $sessions = [];

        foreach ($timings as $timing) {
            $standardMinutes = $this->calculateStandardMinutesEarned($timing);
            $totalActualMinutes += $timing->duration_minutes;
            $totalStandardMinutes += $standardMinutes;

            $sessions[] = (object) [
                'date' => $timing->tanggal,
                'start_time' => $timing->start_time,
                'end_time' => $timing->end_time,
                'project_name' => $timing->project->name ?? 'N/A',
                'job_order_name' => $timing->jobOrder->name ?? 'N/A',
                'step' => $timing->step,
                'measurement_type' => $timing->measurement_type,
                'output_value' => $timing->measurement_value,
                'duration_minutes' => $timing->duration_minutes,
                'standard_minutes' => round($standardMinutes, 2),
                'efficiency' => $timing->duration_minutes > 0 ? round(($standardMinutes / $timing->duration_minutes) * 100, 2) : 0,
            ];
        }

        $overallScore = $totalActualMinutes > 0 ? round(($totalStandardMinutes / $totalActualMinutes) * 100, 2) : 0;

        return [
            'employee_id' => $employeeId,
            'total_sessions' => count($sessions),
            'total_working_minutes' => $totalActualMinutes,
            'total_working_hours' => round($totalActualMinutes / 60, 2),
            'total_standard_minutes' => round($totalStandardMinutes, 2),
            'total_standard_hours' => round($totalStandardMinutes / 60, 2),
            'overall_score' => $overallScore,
            'average_efficiency' => $overallScore, // Same as overall score
            'sessions' => $sessions,
        ];
    }
}
