<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class JobOrder extends Model implements AuditableContract
{
    use \OwenIt\Auditing\Auditable;

    protected $auditInclude = ['project_id', 'department_id', 'name', 'description', 'start_date', 'end_date', 'delivery_date', 'status', 'source_by', 'notes', 'actual_start_date', 'actual_end_date', 'final_image', 'wip_photos'];
    protected $table = 'job_orders';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $fillable = ['id', 'project_id', 'department_id', 'name', 'description', 'start_date', 'end_date', 'delivery_date', 'status', 'source_by', 'notes', 'actual_start_date', 'actual_end_date', 'project_lark', 'department_lark', 'lark_record_id', 'last_sync_at', 'final_image', 'wip_photos', 'total_standard_minutes', 'standard_time_per_unit'];

    protected $dates = ['start_date', 'end_date', 'actual_start_date', 'actual_end_date', 'last_sync_at'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'delivery_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        'total_standard_minutes' => 'integer',
        'standard_time_per_unit' => 'decimal:2',
        'status' => 'string',
        'wip_photos' => 'array',
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

    /**
     * Many-to-many relationship with departments
     * Allows job order to be associated with multiple departments from Lark
     */
    public function departments()
    {
        return $this->belongsToMany(\App\Models\Admin\Department::class, 'job_order_department', 'job_order_id', 'department_id')->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'created_by');
    }

    public function materialRequests()
    {
        return $this->hasMany(\App\Models\Logistic\MaterialRequest::class, 'job_order_id', 'id');
    }

    // Timings relation (one-to-many) - untuk efficiency dashboard
    public function timings()
    {
        return $this->hasMany(Timing::class, 'job_order_id', 'id');
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

    /**
     * Accessor for days until delivery
     * Calculates difference between delivery_date and today
     * Returns null if delivery_date not set
     */
    public function getDaysUntilDeliveryAttribute(): ?int
    {
        if (!$this->delivery_date) {
            return null;
        }

        return \Carbon\Carbon::today()->diffInDays($this->delivery_date, false);
    }

    /**
     * Accessor for delivery date display
     * Returns formatted string with days remaining
     *
     * SPECIAL RULE: If status == "Delivered", show "Delivered" instead of date
     */
    public function getDeliveryDisplayAttribute(): ?string
    {
        // Rule: If job is delivered, always show "Delivered" status
        if ($this->status && strtolower($this->status) === 'delivered') {
            return 'Delivered';
        }

        if (!$this->delivery_date) {
            return null;
        }

        $daysUntil = $this->days_until_delivery;

        if ($daysUntil === null) {
            return $this->delivery_date->format('Y-m-d');
        }

        if ($daysUntil < 0) {
            return 'Overdue (' . abs($daysUntil) . ' days ago)';
        }

        if ($daysUntil === 0) {
            return 'Today';
        }

        if ($daysUntil === 1) {
            return '1 day left';
        }

        return $daysUntil . ' days left';
    }

    /**
     * Check if delivery is urgent (2 days or less before delivery_date)
     * Useful for notification triggers via scheduler
     */
    public function isDeliveryUrgent(): bool
    {
        $daysUntil = $this->days_until_delivery;
        return $daysUntil !== null && $daysUntil >= 0 && $daysUntil <= 2;
    }

    /**
     * Check if job order is delivered
     *
     * @return bool
     */
    public function isDelivered(): bool
    {
        return $this->status && strtolower($this->status) === 'delivered';
    }

    /**
     * Accessor: returns public URL for final_image if the file actually exists on disk.
     * Returns null if final_image is not set or the file is missing.
     */
    public function getFinalImageUrlAttribute(): ?string
    {
        if (empty($this->final_image)) {
            return null;
        }
        if (!Storage::disk('public')->exists($this->final_image)) {
            return null;
        }
        return asset('storage/' . $this->final_image);
    }

    /**
     * Returns true if final_image is set AND the file exists on disk.
     */
    public function hasFinalImage(): bool
    {
        return !empty($this->final_image) && Storage::disk('public')->exists($this->final_image);
    }

    /**
     * Accessor: returns array of public URLs for all wip_photos.
     * Handles both Lark direct URLs (https://...) and legacy local storage paths.
     */
    public function getWipPhotosUrlsAttribute(): array
    {
        $paths = $this->wip_photos ?? [];
        return array_values(array_filter(array_map(function ($p) {
            if (!$p) return null;
            // Lark URLs (url or tmp_url field) — use directly, no storage/ prefix
            if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://')) {
                return $p;
            }
            // Legacy: local path stored in storage/
            return asset('storage/' . $p);
        }, $paths)));
    }

    /**
     * Returns true if wip_photos JSON has entries.
     */
    public function hasWipPhotos(): bool
    {
        return !empty($this->wip_photos);
    }
}
