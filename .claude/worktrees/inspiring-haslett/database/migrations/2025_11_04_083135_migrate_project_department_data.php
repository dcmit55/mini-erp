<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        // Migrate old department_id to pivot table
        DB::table('projects')
            ->whereNotNull('department_id')
            ->get()
            ->each(function ($project) {
                DB::table('department_project')->insert([
                    'department_id' => $project->department_id,
                    'project_id' => $project->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        // Optional: Drop old column after migration
        // Schema::table('projects', function (Blueprint $table) {
        //     $table->dropForeign(['department_id']);
        //     $table->dropColumn('department_id');
        // });
    }

    public function down()
    {
        // Restore department_id if needed
    }
};
