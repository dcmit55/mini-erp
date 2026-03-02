<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Production\Project;
use App\Models\Admin\Department;

class DepartmentMigrationSeeder extends Seeder
{
    public function run()
    {
        // 1. Ambil semua department unik dari projects
        $departments = Project::pluck('department')->unique()->filter();

        // 2. Insert ke tabel departments
        foreach ($departments as $deptName) {
            Department::firstOrCreate(['name' => $deptName]);
        }

        // 3. Update setiap project dengan department_id
        foreach (Project::all() as $project) {
            $dept = Department::where('name', $project->department)->first();
            if ($dept) {
                $project->department_id = $dept->id;
                $project->save();
            }
        }
    }
}
