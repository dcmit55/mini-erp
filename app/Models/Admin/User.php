<?php

namespace App\Models\Admin;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class User extends Authenticatable implements AuditableContract
{
    use HasApiTokens, SoftDeletes, \OwenIt\Auditing\Auditable;
    use HasFactory, Notifiable;

    protected $fillable = ['username', 'password', 'role', 'department_id'];

    protected $hidden = ['password', 'remember_token'];

    protected $auditInclude = ['username', 'role', 'department_id'];

    protected $auditTimestamps = true;

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
