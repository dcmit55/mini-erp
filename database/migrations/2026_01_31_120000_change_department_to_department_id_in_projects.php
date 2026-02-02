<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Admin\Department;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Migrate data from department (text) to department_id (foreign key)
        $departments = Department::all()->keyBy('name');

        DB::table('projects')
            ->whereNotNull('department')
            ->chunkById(100, function ($projects) use ($departments) {
                foreach ($projects as $project) {
                    // Parse department value (bisa comma-separated)
                    $departmentText = $project->department;

                    if (!$departmentText) {
                        continue;
                    }

                    // Ambil department pertama jika ada multiple (comma-separated)
                    $departmentNames = array_map('trim', explode(',', $departmentText));
                    $primaryDepartmentName = $departmentNames[0];

                    // Cari ID department berdasarkan nama (case-insensitive)
                    $department = $departments->first(function ($dept) use ($primaryDepartmentName) {
                        return strtolower($dept->name) === strtolower($primaryDepartmentName);
                    });

                    if ($department) {
                        DB::table('projects')
                            ->where('id', $project->id)
                            ->update(['department_id' => $department->id]);

                        // Jika ada multiple departments, insert ke pivot table
                        if (count($departmentNames) > 1) {
                            foreach ($departmentNames as $deptName) {
                                $dept = $departments->first(function ($d) use ($deptName) {
                                    return strtolower($d->name) === strtolower($deptName);
                                });

                                if ($dept) {
                                    // Check if not exists to avoid duplicates
                                    $exists = DB::table('department_project')->where('project_id', $project->id)->where('department_id', $dept->id)->exists();

                                    if (!$exists) {
                                        DB::table('department_project')->insert([
                                            'project_id' => $project->id,
                                            'department_id' => $dept->id,
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]);
                                    }
                                }
                            }
                        }
                    } else {
                        // Log unmatched departments
                        \Log::warning('Department not found for project', [
                            'project_id' => $project->id,
                            'department_text' => $primaryDepartmentName,
                        ]);
                    }
                }
            });

        // Step 2: Add foreign key constraint if not exists
        Schema::table('projects', function (Blueprint $table) {
            // Check if foreign key exists
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'projects'
                AND COLUMN_NAME = 'department_id'
                AND CONSTRAINT_NAME LIKE 'projects_department_id%'
            ");

            if (empty($foreignKeys)) {
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            }
        });

        // Step 3: Drop old department column
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back department column
        Schema::table('projects', function (Blueprint $table) {
            $table->string('department')->nullable()->after('name');
        });

        // Restore data from department_id to department text
        DB::table('projects')
            ->whereNotNull('department_id')
            ->chunkById(100, function ($projects) {
                foreach ($projects as $project) {
                    $department = Department::find($project->department_id);

                    if ($department) {
                        DB::table('projects')
                            ->where('id', $project->id)
                            ->update(['department' => $department->name]);
                    }
                }
            });

        // Remove foreign key if exists
        Schema::table('projects', function (Blueprint $table) {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'projects'
                AND COLUMN_NAME = 'department_id'
                AND CONSTRAINT_NAME LIKE 'projects_department_id%'
            ");

            if (!empty($foreignKeys)) {
                $table->dropForeign(['department_id']);
            }
        });
    }
};
