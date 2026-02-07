<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;

class JobOrder extends Model
{
    protected $table = 'job_orders';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $fillable = ['id', 'project_id', 'department_id', 'name', 'description', 'start_date', 'end_date', 'source_by', 'notes', 'actual_start_date', 'actual_end_date', 'project_lark', 'department_lark', 'lark_record_id', 'last_sync_at'];

    protected $dates = ['start_date', 'end_date', 'actual_start_date', 'actual_end_date', 'last_sync_at'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = self::generateJobOrderId();
            }
        });
    }

    public static function generateJobOrderId()
    {
        $date = date('ymd');
        $prefix = 'JO-' . $date;

        $lastJobOrder = self::where('id', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastJobOrder) {
            $lastSequence = intval(substr($lastJobOrder->id, -3));
            $sequence = $lastSequence + 1;
        }

        return $prefix . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\Admin\Department::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'created_by');
    }

    public function materialRequests()
    {
        return $this->hasMany(\App\Models\Logistic\MaterialRequest::class, 'job_order_id', 'id');
    }

    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }
        return $query;
    }
}
