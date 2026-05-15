<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class JobOrder extends Model implements AuditableContract
{
    use \OwenIt\Auditing\Auditable;

    protected $auditInclude = ['project_id', 'department_id', 'name', 'description', 'start_date', 'end_date', 'delivery_date', 'status', 'source_by', 'notes', 'actual_start_date', 'actual_end_date', 'final_image', 'project_images', 'latest_designs', 'final_images', 'wip_photos'];
    protected $table = 'job_orders';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $fillable = ['id', 'project_id', 'department_id', 'name', 'description', 'start_date', 'end_date', 'delivery_date', 'status', 'source_by', 'notes', 'actual_start_date', 'actual_end_date', 'project_lark', 'department_lark', 'lark_record_id', 'last_sync_at', 'final_image', 'project_images', 'latest_designs', 'final_images', 'wip_photos', 'lark_photo_tokens', 'total_standard_minutes', 'standard_time_per_unit'];

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
        'lark_photo_tokens' => 'array',
        'last_sync_at' => 'datetime',
        'project_images' => 'array',
        'latest_designs' => 'array',
        'final_images' => 'array',
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
     * Convert a stored Lark URL (or local storage path) to a browser-accessible URL.
     *
     * WHY PROXY?
     * Lark /download and batch_get_tmp_download_url URLs require Bearer auth.
     * Browsers cannot send Bearer tokens in <img src> requests.
     * The proxy route (/lark-media) fetches server-side with auth, then redirects
     * to a pre-signed URL that the browser can load directly.
     *
     * @param string|null $storedUrl  Value from DB (Lark URL or local path)
     * @return string|null
     */
    public static function toLarkProxyUrl(?string $storedUrl): ?string
    {
        if (!$storedUrl) {
            return null;
        }

        // Lark URL — route through proxy
        if (str_contains($storedUrl, 'larksuite.com')) {
            return route('lark.media', ['u' => base64_encode($storedUrl)]);
        }

        // Legacy local storage path (e.g. 'job_order_images/foo.jpg')
        return asset('storage/' . $storedUrl);
    }

    /**
     * Accessor: returns browser-accessible URL for final_image.
     * Routes Lark URLs through the proxy; local paths through storage/.
     */
    public function getFinalImageUrlAttribute(): ?string
    {
        return static::toLarkProxyUrl($this->final_image);
    }

    /**
     * Returns true if final_image is set.
     */
    public function hasFinalImage(): bool
    {
        return !empty($this->final_image);
    }

    /**
     * Accessor: first wip_photo local path.
     * Used by gallery-index.blade.php (wip_photo / wip_photo_url).
     */
    public function getWipPhotoAttribute(): ?string
    {
        $photos = $this->wip_photos ?? [];
        return $photos[0] ?? null;
    }

    /**
     * Accessor: browser URL for the FIRST wip_photo.
     * Local path  → asset('storage/wip_photos/xxx.jpg')  — no API call, works offline.
     * Legacy Lark URL (pre-migration) → toLarkProxyUrl() as fallback until backfill runs.
     */
    public function getWipPhotoUrlAttribute(): ?string
    {
        $path = $this->wip_photo;
        if (!$path) {
            return null;
        }
        if (!str_starts_with($path, 'http')) {
            return asset('storage/' . $path); // local storage — offline-capable
        }
        return static::toLarkProxyUrl($path); // legacy Lark URL fallback
    }

    /**
     * Accessor: browser-accessible URLs for ALL wip_photos.
     * Local paths → asset('storage/') — served directly, no proxy, no API call.
     * Legacy Lark URLs (pre-migration) → proxy fallback until backfill migrates them.
     */
    public function getWipPhotosUrlsAttribute(): array
    {
        $paths = $this->wip_photos ?? [];
        return array_values(
            array_filter(
                array_map(function (string $p): ?string {
                    if (!$p) {
                        return null;
                    }
                    // Local path → direct storage URL (offline-capable, no API dependency)
                    if (!str_starts_with($p, 'http')) {
                        return asset('storage/' . $p);
                    }
                    // Legacy Lark URL — proxy fallback (until backfill migrates to local)
                    return static::toLarkProxyUrl($p);
                }, $paths),
            ),
        );
    }

    /**
     * Returns true if wip_photos JSON has entries.
     */
    public function hasWipPhotos(): bool
    {
        return !empty($this->wip_photos);
    }
}
