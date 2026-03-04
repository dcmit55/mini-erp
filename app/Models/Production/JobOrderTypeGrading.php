<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class JobOrderTypeGrading extends Model
{
    use SoftDeletes;

    protected $table = 'job_order_type_gradings';

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    protected $fillable = [
        'uid',
        'job_type_grade',
        'score',
        'grading',
        'job_type',
        'product_sub_category',
        'other_details',
        'category_id',
        'parent_items',
        'lark_record_id',
        'last_sync_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'last_sync_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
        });
    }

    // Relationships

    public function departments()
    {
        return $this->belongsToMany(
            \App\Models\Admin\Department::class,
            'department_job_order_type_grading',
            'job_order_type_grading_id',
            'department_id'
        )->withTimestamps();
    }

    public function category()
    {
        return $this->belongsTo(\App\Models\Logistic\Category::class);
    }

    public function jobOrders()
    {
        return $this->hasMany(JobOrder::class, 'job_type_grade_id');
    }
}