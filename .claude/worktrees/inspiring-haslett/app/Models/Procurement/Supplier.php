<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Procurement\LocationSupplier;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Supplier extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $fillable = ['supplier_code', 'name', 'contact_person', 'address', 'location_id', 'referral_link', 'lead_time_days', 'status', 'remark'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['status_badge', 'formatted_referral_link'];

    protected $auditInclude = ['supplier_code', 'name', 'contact_person', 'address', 'location_id', 'referral_link', 'lead_time_days', 'status', 'remark'];

    protected $auditTimestamps = true;

    /**
     * Relasi ke LocationSupplier
     */
    public function location()
    {
        return $this->belongsTo(LocationSupplier::class, 'location_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope untuk supplier yang tidak blacklisted
     */
    public function scopeNonBlacklisted($query)
    {
        return $query->where('status', '!=', 'blacklisted');
    }

    public function getStatusBadgeAttribute()
    {
        return match ($this->status) {
            'active' => 'success',
            'inactive' => 'secondary',
            'blacklisted' => 'danger',
            default => 'secondary',
        };
    }

    public function getFormattedReferralLinkAttribute()
    {
        if (!$this->referral_link) {
            return null;
        }
        $link = $this->referral_link;
        if (!preg_match('~^(?:f|ht)tps?://~i', $link)) {
            $link = 'http://' . $link;
        }
        return $link;
    }
}
