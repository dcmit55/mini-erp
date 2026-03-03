<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Admin\User;

class FeatureAnnouncement extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'version', 'priority', 'target_roles', 'target_user_ids', 'is_active', 'show_from', 'show_until'];

    protected $casts = [
        'target_roles' => 'array',
        'target_user_ids' => 'array',
        'is_active' => 'boolean',
        'show_from' => 'datetime',
        'show_until' => 'datetime',
    ];

    /**
     * Relationship dengan reads tracking
     */
    public function reads()
    {
        return $this->hasMany(FeatureAnnouncementRead::class, 'announcement_id');
    }

    /**
     * Check apakah announcement ini ditargetkan untuk user tertentu
     */
    public function isTargetedTo(User $user): bool
    {
        // Check roles
        if ($this->target_roles && in_array($user->role, $this->target_roles)) {
            return true;
        }

        // Check specific users
        if ($this->target_user_ids && in_array($user->id, $this->target_user_ids)) {
            return true;
        }

        // Jika tidak ada target, berarti untuk semua user
        if (empty($this->target_roles) && empty($this->target_user_ids)) {
            return true;
        }

        return false;
    }

    /**
     * Check apakah user sudah read announcement ini
     */
    public function isReadBy(User $user): bool
    {
        return $this->reads()->where('user_id', $user->id)->exists();
    }

    /**
     * Check apakah announcement masih dalam periode show
     */
    public function isCurrentlyShowing(): bool
    {
        $now = now();

        if ($this->show_from && $now->lt($this->show_from)) {
            return false;
        }

        if ($this->show_until && $now->gt($this->show_until)) {
            return false;
        }

        return true;
    }

    /**
     * Scope untuk announcement yang aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk announcement yang sedang showing
     */
    public function scopeCurrentlyShowing($query)
    {
        return $query
            ->where(function ($q) {
                $q->whereNull('show_from')->orWhere('show_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('show_until')->orWhere('show_until', '>=', now());
            });
    }

    /**
     * Scope untuk announcement yang belum dibaca oleh user
     */
    public function scopeUnreadBy($query, User $user)
    {
        return $query->whereDoesntHave('reads', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    /**
     * Get semua user IDs yang ditargetkan
     */
    public function getTargetUserIds(): array
    {
        $targetUserIds = [];

        // Ambil user berdasarkan roles
        if ($this->target_roles) {
            $usersByRole = User::whereIn('role', $this->target_roles)->pluck('id')->toArray();
            $targetUserIds = array_merge($targetUserIds, $usersByRole);
        }

        // Tambahkan specific user IDs
        if ($this->target_user_ids) {
            $targetUserIds = array_merge($targetUserIds, $this->target_user_ids);
        }

        // Jika kosong, berarti semua user
        if (empty($targetUserIds)) {
            $targetUserIds = User::pluck('id')->toArray();
        }

        return array_unique($targetUserIds);
    }
}
