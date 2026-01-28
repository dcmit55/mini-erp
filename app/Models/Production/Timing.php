<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;
use App\Models\Production\Project;
use App\Models\Hr\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Timing extends Model
{
    use HasFactory;

    protected $fillable = ['tanggal', 'project_id', 'step', 'parts', 'employee_id', 'start_time', 'end_time', 'output_qty', 'status', 'remarks'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
