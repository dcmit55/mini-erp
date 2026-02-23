<?php

namespace App\Helpers;

/**
 * Time Helper
 *
 * Standardized time conversion utilities
 * PRIMARY UNIT: MINUTES (integer)
 * All calculations should use minutes to avoid precision loss
 */
class TimeHelper
{
    /**
     * Convert hours to minutes
     *
     * @param float $hours
     * @return int
     */
    public static function hoursToMinutes(float $hours): int
    {
        return (int) round($hours * 60);
    }

    /**
     * Convert minutes to hours (decimal)
     *
     * @param int $minutes
     * @param int $precision
     * @return float
     */
    public static function minutesToHours(int $minutes, int $precision = 2): float
    {
        return round($minutes / 60, $precision);
    }

    /**
     * Format minutes as HH:MM string
     *
     * @param int $minutes
     * @return string
     */
    public static function minutesToHHMM(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    }

    /**
     * Format minutes as readable text
     * Examples: "2 hours 30 minutes", "45 minutes", "1 hour"
     *
     * @param int $minutes
     * @return string
     */
    public static function minutesToReadable(int $minutes): string
    {
        if ($minutes == 0) {
            return '0 minutes';
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours > 0 && $mins > 0) {
            return "{$hours} " . ($hours == 1 ? 'hour' : 'hours') . " {$mins} " . ($mins == 1 ? 'minute' : 'minutes');
        }

        if ($hours > 0) {
            return "{$hours} " . ($hours == 1 ? 'hour' : 'hours');
        }

        return "{$mins} " . ($mins == 1 ? 'minute' : 'minutes');
    }

    /**
     * Parse HH:MM string to minutes
     *
     * @param string $time Format: "HH:MM" or "H:M"
     * @return int
     */
    public static function hhmmToMinutes(string $time): int
    {
        if (empty($time)) {
            return 0;
        }

        $parts = explode(':', $time);
        if (count($parts) !== 2) {
            return 0;
        }

        $hours = (int) $parts[0];
        $minutes = (int) $parts[1];

        return $hours * 60 + $minutes;
    }

    /**
     * Calculate efficiency percentage
     *
     * Formula: (output / actual_minutes) * 60 = output per hour
     * This normalizes efficiency to "units per hour" scale
     *
     * @param float $output
     * @param int $actualMinutes
     * @param int $precision
     * @return float
     */
    public static function calculateEfficiency(float $output, int $actualMinutes, int $precision = 2): float
    {
        if ($actualMinutes <= 0) {
            return 0.0;
        }

        // Output per hour
        return round(($output / $actualMinutes) * 60, $precision);
    }

    /**
     * Calculate productivity percentage (with standard minutes)
     *
     * Formula: (standard_minutes_earned / actual_minutes_worked) * 100
     * This is the industrial engineering standard for productivity measurement
     *
     * @param float $standardMinutesEarned
     * @param int $actualMinutes
     * @param int $precision
     * @return float
     */
    public static function calculateProductivity(float $standardMinutesEarned, int $actualMinutes, int $precision = 2): float
    {
        if ($actualMinutes <= 0) {
            return 0.0;
        }

        return round(($standardMinutesEarned / $actualMinutes) * 100, $precision);
    }

    /**
     * Calculate standard minutes earned for progress-based work
     *
     * @param float $progressPercentage (0-100)
     * @param int $totalStandardMinutes
     * @return float
     */
    public static function calculateStandardMinutesForProgress(float $progressPercentage, int $totalStandardMinutes): float
    {
        if ($totalStandardMinutes <= 0) {
            return 0.0;
        }

        return ($progressPercentage / 100) * $totalStandardMinutes;
    }

    /**
     * Calculate standard minutes earned for quantity-based work
     *
     * @param float $quantity
     * @param float $standardTimePerUnit (in minutes)
     * @return float
     */
    public static function calculateStandardMinutesForQuantity(float $quantity, float $standardTimePerUnit): float
    {
        return $quantity * $standardTimePerUnit;
    }

    /**
     * Validate and sanitize minutes value
     * Ensures non-negative integer
     *
     * @param mixed $value
     * @return int
     */
    public static function sanitizeMinutes($value): int
    {
        $minutes = (int) $value;
        return max(0, $minutes);
    }

    /**
     * Check if efficiency is realistic (not exceeding maximum threshold)
     *
     * @param float $efficiency
     * @param float $maxEfficiency Default 100%
     * @return bool
     */
    public static function isEfficiencyRealistic(float $efficiency, float $maxEfficiency = 100.0): bool
    {
        return $efficiency >= 0 && $efficiency <= $maxEfficiency;
    }

    /**
     * Cap efficiency at maximum value
     *
     * @param float $efficiency
     * @param float $maxEfficiency Default 100%
     * @return float
     */
    public static function capEfficiency(float $efficiency, float $maxEfficiency = 100.0): float
    {
        return min(max(0, $efficiency), $maxEfficiency);
    }
}
