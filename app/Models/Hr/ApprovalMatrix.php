<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApprovalMatrix extends Model
{
    protected $table = 'approval_matrix';

    protected $fillable = [
        'uid',
        'module',
        'level',
        'role',
        'delegate_roles',
    ];

    protected $casts = [
        'delegate_roles' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
        });
    }

    /**
     * Ambil semua level untuk satu module, urut dari level terkecil.
     */
    public static function levelsFor(string $module): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('module', $module)->orderBy('level')->get();
    }

    /**
     * Hitung total level approval untuk satu module.
     */
    public static function totalLevels(string $module): int
    {
        return static::where('module', $module)->count();
    }

    /**
     * Semua role yang diizinkan bertindak pada level ini.
     * Menggabungkan role utama + delegate_roles.
     *
     * @return string[]
     */
    public function getAllowedRoles(): array
    {
        return array_values(array_filter(array_merge(
            [$this->role],
            $this->delegate_roles ?? []
        )));
    }
}
