<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Production\Project;

class Department extends Model
{
    protected $fillable = ['uid', 'name'];

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
        });
    }

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
