<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobOrder extends Model
{
    use SoftDeletes;

    protected $table = 'job_orders';
    
    // ID adalah string custom (JO-26013001), bukan auto-increment
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'id',
        'project_id',
        'department_id',
        'name',           // HAPUS joborder_name, hanya name saja
        'description',
        'start_date',
        'end_date',
        'assigned_to',
        'created_by',
        'notes',
        'actual_start_date',
        'actual_end_date',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'actual_start_date',
        'actual_end_date',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
    ];

    /**
     * Boot method untuk generate ID otomatis
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = self::generateJobOrderId();
            }
        });
    }
    
    /**
     * Generate custom Job Order ID
     * Format: JO-YYMMDDXXX
     * Contoh: JO-260130001
     */
    public static function generateJobOrderId()
    {
        $date = date('ymd');
        $prefix = 'JO-' . $date;
        
        // Cari sequence terakhir hari ini
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
    
    public function assignee()
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'assigned_to');
    }
    
    public function creator()
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'created_by');
    }
    
    /**
     * Scope untuk filtering status
     */
    public function scopeStatus($query, $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }
    
    /**
     * Scope untuk filtering priority
     */
    public function scopePriority($query, $priority)
    {
        if ($priority) {
            return $query->where('priority', $priority);
        }
        return $query;
    }
    
    /**
     * Scope untuk search
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        return $query;
    }
}