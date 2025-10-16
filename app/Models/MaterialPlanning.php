<?php
// filepath: c:\xampp\htdocs\inventory-system-v2-upg-larv-oct\app\Models\MaterialPlanning.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialPlanning extends Model
{
    protected $guarded = [];

    protected $dates = ['created_at', 'updated_at', 'eta_date'];

    public function project()
    {
        return $this->belongsTo(\App\Models\Project::class);
    }

    public function unit()
    {
        return $this->belongsTo(\App\Models\Unit::class);
    }

    public function requester()
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }

    // Scope untuk filter berdasarkan department
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->whereHas('project', function ($q) use ($departmentId) {
            $q->where('department_id', $departmentId);
        });
    }

    // Scope untuk filter berdasarkan order type
    public function scopeByOrderType($query, $orderType)
    {
        return $query->where('order_type', $orderType);
    }

    // Scope untuk filter berdasarkan tanggal
    public function scopeByDateRange($query, $from = null, $to = null)
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }
}
