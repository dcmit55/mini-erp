<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use App\Models\Production\Project;

class Department extends Model
{
    protected $fillable = ['name'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Ubah relasi dari hasMany ke belongsToMany
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'department_project')->withTimestamps();
    }

    public function jobOrderTypeGradings()
    {
        return $this->belongsToMany(
            \App\Models\Production\JobOrderTypeGrading::class,
            'department_job_order_type_grading',
            'department_id',
            'job_order_type_grading_id'
        )->withTimestamps();
    }
}
