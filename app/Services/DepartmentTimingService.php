<?php

namespace App\Services;

use App\Models\Admin\Department;
use Illuminate\Support\Facades\Cache;

class DepartmentTimingService
{
    /**
     * Get department-specific configuration for timing
     *
     * @param int $departmentId
     * @return array
     */
    public function getDepartmentConfig($departmentId)
    {
        $cacheKey = "dept_timing_config_{$departmentId}";

        return Cache::remember($cacheKey, 3600, function () use ($departmentId) {
            $department = Department::find($departmentId);

            if (!$department) {
                return $this->getDefaultConfig();
            }

            // Department-specific configurations
            $configs = [
                // Animatronics/Animation Department
                'animatronics' => [
                    'common_steps' => ['Sculpting', 'Mold Making', 'Casting', 'Mechanism Assembly', 'Electronics Install', 'Skin Application', 'Painting', 'Hair Punching', 'Final Assembly', 'Programming', 'Testing'],
                    'common_parts' => ['Head', 'Body', 'Arms', 'Legs', 'Hands', 'Feet', 'Face', 'Eyes', 'Mouth', 'Full Figure'],
                    'tracking_modes' => ['timer', 'progress'],
                    'requires_photo' => true,
                    'measurement_types' => ['quantity', 'percentage'],
                ],

                // Costume Department
                'costume' => [
                    'common_steps' => ['Pattern Making', 'Fabric Cutting', 'Sewing', 'Fitting', 'Detailing', 'Final Touches'],
                    'common_parts' => ['Top', 'Bottom', 'Full Costume', 'Accessories', 'Headpiece'],
                    'tracking_modes' => ['timer'],
                    'requires_photo' => false,
                    'measurement_types' => ['quantity'],
                ],

                // Props Department
                'props' => [
                    'common_steps' => ['Design', 'Fabrication', 'Painting', 'Weathering', 'Assembly', 'Finishing'],
                    'common_parts' => ['Small Props', 'Large Props', 'Set Pieces', 'Accessories'],
                    'tracking_modes' => ['timer'],
                    'requires_photo' => false,
                    'measurement_types' => ['quantity'],
                ],
            ];

            // Match department name to config
            $deptName = strtolower($department->name);

            if (str_contains($deptName, 'animatronic') || str_contains($deptName, 'animation')) {
                return $configs['animatronics'];
            } elseif (str_contains($deptName, 'costume')) {
                return $configs['costume'];
            } elseif (str_contains($deptName, 'prop')) {
                return $configs['props'];
            }

            return $this->getDefaultConfig();
        });
    }

    /**
     * Get default configuration
     *
     * @return array
     */
    protected function getDefaultConfig()
    {
        return [
            'common_steps' => ['Preparation', 'Production', 'Quality Check', 'Finishing'],
            'common_parts' => ['Component A', 'Component B', 'Full Assembly'],
            'tracking_modes' => ['timer'],
            'requires_photo' => false,
            'measurement_types' => ['quantity'],
        ];
    }

    /**
     * Clear department config cache
     *
     * @param int|null $departmentId
     * @return void
     */
    public function clearCache($departmentId = null)
    {
        if ($departmentId) {
            Cache::forget("dept_timing_config_{$departmentId}");
        } else {
            // Clear all department timing configs
            Cache::flush();
        }
    }
}
