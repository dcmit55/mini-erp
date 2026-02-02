<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Panggil seeder lainnya dulu jika ada
        // $this->call(UsersTableSeeder::class);
        
        // Tambahkan job orders langsung
        $this->seedJobOrders();
    }
    
    private function seedJobOrders()
    {
        // Cek apakah sudah ada data
        $count = DB::table('job_orders')->count();
        
        if ($count == 0) {
            // Ambil ID pertama dari tabel terkait
            $userId = DB::table('users')->value('id') ?? 1;
            $projectId = DB::table('projects')->value('id') ?? 1;
            $departmentId = DB::table('departments')->value('id') ?? 1;
            
            // Jika tabel terkait juga kosong, buat data dummy
            if (!$userId) {
                $userId = DB::table('users')->insertGetId([
                    'username' => 'admin',
                    'password' => bcrypt('password'),
                    'role' => 'super_admin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            if (!$projectId) {
                $projectId = DB::table('projects')->insertGetId([
                    'name' => 'Project Default',
                    'code' => 'PROJ001',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            if (!$departmentId) {
                $departmentId = DB::table('departments')->insertGetId([
                    'name' => 'Production',
                    'code' => 'DEPT001',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            // Insert job orders
            DB::table('job_orders')->insert([
                [
                    'name' => 'Install Production Machine',
                    'project_id' => $projectId,
                    'department_id' => $departmentId,
                    'assigned_to' => $userId,
                    'description' => 'Install new production machine',
                    'status' => 'in_progress',
                    'priority' => 'high',
                    'start_date' => now()->subDays(3),
                    'end_date' => now()->addDays(7),
                    'created_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Monthly Maintenance',
                    'project_id' => $projectId,
                    'department_id' => $departmentId,
                    'assigned_to' => $userId,
                    'description' => 'Routine monthly maintenance',
                    'status' => 'pending',
                    'priority' => 'medium',
                    'start_date' => now(),
                    'end_date' => now()->addDays(5),
                    'created_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
            
            echo "Job orders seeded successfully!\n";
        } else {
            echo "Job orders already exist ($count records)\n";
        }
    }
}