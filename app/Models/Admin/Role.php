<?php

namespace App\Models\Admin;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected static function booting(): void
    {
        parent::booting();

        static::creating(function (self $role) {
            if (empty($role->uid)) {
                $role->uid = Str::uuid()->toString();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }
}
