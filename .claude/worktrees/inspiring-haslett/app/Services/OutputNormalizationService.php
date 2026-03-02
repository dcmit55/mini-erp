<?php

namespace App\Services;

use App\Models\Production\Timing;
use Illuminate\Support\Collection;

/**
 * Output Normalization Service
 *
 * PROBLEM: Job orders memiliki mixed measurement types:
 * - 'progress' type: Output dalam % (0-100)
 * - 'qty/pcs/unit' type: Output dalam quantity (bisa ribuan)
 *
 * Tidak bisa dijumlahkan langsung: 50% + 100pcs = ??? (apples to oranges)
 *
 * SOLUTION: Normalize semua output ke "Equivalent Work Hours" atau "Standard Minutes Earned"
 */
class OutputNormalizationService
{
    /**
     * OPTION 1: Normalize ke Standard Minutes Earned
     *
     * Semua output dikonversi ke "berapa menit standard work" yang dihasilkan
     * Ini yang paling fair karena memperhitungkan effort/complexity pekerjaan
     *
     * @param Collection|array $timings
     * @return float Total standard minutes earned
     */
    public function normalizeToStandardMinutes($timings): float
    {
        $performanceService = app(\App\Services\EmployeePerformanceService::class);
        $totalStandardMinutes = 0;

        foreach ($timings as $timing) {
            $totalStandardMinutes += $performanceService->calculateStandardMinutesEarned($timing);
        }

        return round($totalStandardMinutes, 2);
    }

    /**
     * OPTION 2: Normalize ke Equivalent Units (Weighted)
     *
     * Konversi progress % ke equivalent units berdasarkan job complexity
     * Formula: equivalent_units = (progress% / 100) × (total_standard_minutes / avg_time_per_unit)
     *
     * Example:
     * - Job A: 50% progress, total_standard = 2400 min, avg_time = 30 min/unit
     *   → Equivalent = (50/100) × (2400/30) = 0.5 × 80 = 40 units
     *
     * - Job B: 100 units actual
     *   → Equivalent = 100 units
     *
     * Total Output = 40 + 100 = 140 equivalent units
     *
     * @param Collection|array $timings
     * @param float $averageTimePerUnit Default time per unit if not configured
     * @return float Total equivalent units
     */
    public function normalizeToEquivalentUnits($timings, float $averageTimePerUnit = 30.0): float
    {
        $totalEquivalentUnits = 0;

        foreach ($timings as $timing) {
            $measurementType = $timing->measurement_type;
            $measurementValue = $timing->measurement_value ?? 0;

            if ($measurementType === 'progress' || $measurementType === 'percentage') {
                // Convert progress to equivalent units
                $jobOrder = $timing->jobOrder;

                if ($jobOrder && $jobOrder->total_standard_minutes > 0) {
                    // Use job-specific standard
                    $standardMinutesForProgress = ($measurementValue / 100) * $jobOrder->total_standard_minutes;
                    $equivalentUnits = $standardMinutesForProgress / $averageTimePerUnit;
                } else {
                    // Fallback: assume 1% = 1 equivalent unit
                    $equivalentUnits = $measurementValue;
                }

                $totalEquivalentUnits += $equivalentUnits;
            } else {
                // Already in units, use directly
                $totalEquivalentUnits += $measurementValue;
            }
        }

        return round($totalEquivalentUnits, 2);
    }

    /**
     * OPTION 3: Normalize ke Work Hours Weighted
     *
     * Semua output dinormalisasi ke "jam kerja ekuivalen"
     * Lebih mudah dipahami management (dalam satuan jam)
     *
     * @param Collection|array $timings
     * @return float Total work hours weighted
     */
    public function normalizeToWorkHours($timings): float
    {
        $standardMinutes = $this->normalizeToStandardMinutes($timings);
        return round($standardMinutes / 60, 2); // Convert to hours
    }

    /**
     * OPTION 4: Separate Metrics (Recommended for Dashboard)
     *
     * Jangan digabung! Tampilkan terpisah per measurement type
     * Ini paling akurat karena tidak ada konversi/asumsi
     *
     * @param Collection|array $timings
     * @return array Metrics per measurement type
     */
    public function getSeparateMetrics($timings): array
    {
        $metrics = [
            'progress_based' => [
                'total_progress_percentage' => 0,
                'count' => 0,
                'avg_progress' => 0,
            ],
            'quantity_based' => [
                'total_units' => 0,
                'count' => 0,
                'types' => [], // qty, pcs, unit, etc
            ],
            'combined' => [
                'total_standard_minutes' => 0,
                'total_work_hours' => 0,
            ],
        ];

        foreach ($timings as $timing) {
            $measurementType = $timing->measurement_type;
            $measurementValue = $timing->measurement_value ?? 0;

            if ($measurementType === 'progress' || $measurementType === 'percentage') {
                $metrics['progress_based']['total_progress_percentage'] += $measurementValue;
                $metrics['progress_based']['count']++;
            } else {
                $metrics['quantity_based']['total_units'] += $measurementValue;
                $metrics['quantity_based']['count']++;

                if (!isset($metrics['quantity_based']['types'][$measurementType])) {
                    $metrics['quantity_based']['types'][$measurementType] = 0;
                }
                $metrics['quantity_based']['types'][$measurementType] += $measurementValue;
            }
        }

        // Calculate averages
        if ($metrics['progress_based']['count'] > 0) {
            $metrics['progress_based']['avg_progress'] = round($metrics['progress_based']['total_progress_percentage'] / $metrics['progress_based']['count'], 2);
        }

        // Calculate combined metrics
        $metrics['combined']['total_standard_minutes'] = $this->normalizeToStandardMinutes($timings);
        $metrics['combined']['total_work_hours'] = round($metrics['combined']['total_standard_minutes'] / 60, 2);

        return $metrics;
    }

    /**
     * Get recommended display format for efficiency dashboard
     *
     * @param Collection|array $timings
     * @return array Display-ready metrics
     */
    public function getEfficiencyMetrics($timings): array
    {
        $separateMetrics = $this->getSeparateMetrics($timings);

        return [
            // Primary metric: Standard Minutes (most accurate)
            'total_output_standard_minutes' => $separateMetrics['combined']['total_standard_minutes'],
            'total_output_work_hours' => $separateMetrics['combined']['total_work_hours'],

            // Breakdown for transparency
            'progress_items' => $separateMetrics['progress_based']['count'],
            'total_progress_percentage' => $separateMetrics['progress_based']['total_progress_percentage'],
            'avg_progress' => $separateMetrics['progress_based']['avg_progress'],

            'quantity_items' => $separateMetrics['quantity_based']['count'],
            'total_units' => $separateMetrics['quantity_based']['total_units'],
            'units_by_type' => $separateMetrics['quantity_based']['types'],
        ];
    }
}
