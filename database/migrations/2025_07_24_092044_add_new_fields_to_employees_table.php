<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Hr\Employee;

return new class extends Migration {
    public function up()
    {
        // Step 1: Tambah kolom baru (nullable)
        Schema::table('employees', function (Blueprint $table) {
            $table->string('employee_no')->nullable()->after('id');
            $table->string('photo')->nullable()->after('name');
            $table->string('rekening')->nullable()->after('phone');
            $table->integer('saldo_cuti')->default(12)->after('salary');
        });

        // Step 2: Isi employee_no yang kosong/null
        $this->generateEmployeeNumbers();
        \DB::table('employees')
            ->whereNull('employee_no')
            ->update([
                'employee_no' => \DB::raw("CONCAT('DCM-', LPAD(id, 4, '0'))"),
            ]);

        // Step 3: Ubah jadi unique dan not null
        Schema::table('employees', function (Blueprint $table) {
            $table->string('employee_no')->unique()->nullable(false)->change();
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['employee_no', 'photo', 'rekening', 'saldo_cuti']);
        });
    }

    private function generateEmployeeNumbers()
    {
        // Get all employees that don't have employee_no
        $employees = Employee::whereNull('employee_no')->orWhere('employee_no', '')->orderBy('id')->get();

        foreach ($employees as $employee) {
            $number = $employee->id;
            $employeeNo = 'DCM-' . str_pad($number, 4, '0', STR_PAD_LEFT);

            // Check if this employee_no already exists
            while (Employee::where('employee_no', $employeeNo)->exists()) {
                $number++;
                $employeeNo = 'DCM-' . str_pad($number, 4, '0', STR_PAD_LEFT);
            }

            $employee->update(['employee_no' => $employeeNo]);
        }
    }
};
