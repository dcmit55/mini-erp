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
}
