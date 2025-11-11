<?php

namespace App\Models\Admin;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable implements AuditableContract
{
    use HasApiTokens, SoftDeletes, \OwenIt\Auditing\Auditable;
    use HasFactory, Notifiable;

    protected $fillable = ['username', 'password', 'role', 'department_id'];

    protected $hidden = ['password', 'remember_token'];

    protected $auditInclude = ['username', 'role', 'department_id'];

    protected $auditTimestamps = true;

    /**
     * Get cache key untuk track password changes
     */
    public function getPasswordCacheKey()
    {
        return "user_password_hash_{$this->id}";
    }

    /**
     * Store current password hash di cache (30 hari)
     */
    public function cachePasswordHash()
    {
        Cache::put($this->getPasswordCacheKey(), $this->password, now()->addDays(30));
    }

    /**
     * Check if password was changed
     * More robust checking
     */
    public function isPasswordChanged()
    {
        try {
            $cachedHash = Cache::get($this->getPasswordCacheKey());

            // Jika tidak ada cache, berarti user baru, tidak perlu logout
            if (!$cachedHash) {
                return false;
            }

            // Jika cache ada tapi berbeda dengan password di DB, password sudah berubah
            return $cachedHash !== $this->password;
        } catch (\Exception $e) {
            \Log::error("Error checking password change for user {$this->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Invalidate user sessions (force logout)
     * Handle multiple logout methods
     */
    public function invalidateSessions()
    {
        try {
            // 1. Clear cache untuk force logout
            Cache::forget($this->getPasswordCacheKey());

            // 2. Hapus semua token Sanctum
            $this->tokens()->delete();

            // 3. Clear session data jika ada
            Session()->forget("user_{$this->id}_password_hash");

            \Log::info("Sessions invalidated for user {$this->username}");
        } catch (\Exception $e) {
            \Log::error("Error invalidating sessions for user {$this->id}: " . $e->getMessage());
        }
    }

    public function isRole($role)
    {
        return $this->role === $role;
    }

    public function isLogisticAdmin()
    {
        return in_array($this->role, ['admin_logistic', 'super_admin', 'admin_finance']);
    }

    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user is read-only admin
     */
    public function isReadOnlyAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user can modify data (create/edit/delete)
     */
    public function canModifyData()
    {
        return !$this->isReadOnlyAdmin();
    }

    public function isRequestOwner($username)
    {
        return $this->username === $username;
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
