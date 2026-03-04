<?php

namespace App\Models\Hr;

use App\Models\Admin\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApprovalTransaction extends Model
{
    protected $table = 'approval_transactions';

    protected $fillable = [
        'uid',
        'module',
        'reference_id',
        'level',
        'approved_by',
        'status',
        'approved_at',
        'remarks',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
        });
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForReference($query, string $module, int $referenceId)
    {
        return $query->where('module', $module)->where('reference_id', $referenceId);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
