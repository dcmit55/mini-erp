<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, SoftDeletes;
    use HasFactory, Notifiable;

    protected $fillable = ['username', 'password', 'role', 'department_id'];

    protected $hidden = ['password', 'remember_token'];

    public function isRole($role)
    {
        return $this->role === $role;
    }

    public function isLogisticAdmin()
    {
        return in_array($this->role, ['admin_logistic', 'super_admin']);
    }

    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user is read-only admin (visitor)
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
