<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WarningLetter extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uid', 'letter_number', 'employee_id', 'sp_level',
        'violation_cat_id', 'violation_date', 'reason',
        'status', 'template_id', 'pdf_path',
        'issued_date', 'valid_until',
        'batch_id', 'created_by', 'trigger_source',
    ];

    protected $casts = [
        'violation_date' => 'date',
        'issued_date'    => 'date',
        'valid_until'    => 'date',
        'sp_level'       => 'integer',
    ];

    const SP_LABELS = [
        1 => 'SP1 — First Warning',
        2 => 'SP2 — Second Warning',
        3 => 'SP3 — Final Warning',
    ];

    const STATUS_LABELS = [
        'draft'            => 'Draft',
        'pending_approval' => 'Pending Approval',
        'approved'         => 'Approved',
        'acknowledged'     => 'Acknowledged',
        'rejected'         => 'Rejected',
        'expired'          => 'Expired',
    ];

    const STATUS_COLORS = [
        'draft'            => 'secondary',
        'pending_approval' => 'warning',
        'approved'         => 'success',
        'acknowledged'     => 'primary',
        'rejected'         => 'danger',
        'expired'          => 'dark',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function violationCategory()
    {
        return $this->belongsTo(ViolationCategory::class, 'violation_cat_id');
    }

    public function template()
    {
        return $this->belongsTo(WarningTemplate::class, 'template_id');
    }

    public function batch()
    {
        return $this->belongsTo(WarningBatch::class, 'batch_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'created_by');
    }

    public function acknowledgment()
    {
        return $this->hasOne(WarningLetterAcknowledgment::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    /** SP yang masih aktif (belum expired/rejected) */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['expired', 'rejected'])
                     ->where('valid_until', '>=', now()->toDateString());
    }

    public function scopeExpiredToday($query)
    {
        return $query->whereNotIn('status', ['expired', 'rejected', 'draft'])
                     ->where('valid_until', '<', now()->toDateString());
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function getSpLabelAttribute(): string
    {
        return self::SP_LABELS[$this->sp_level] ?? "SP{$this->sp_level}";
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }


}
