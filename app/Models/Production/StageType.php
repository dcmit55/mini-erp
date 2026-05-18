<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;

class StageType extends Model
{
    protected $fillable = ['name'];

    public function stages()
    {
        return $this->hasMany(Stage::class)->orderBy('sequence');
    }

    public function activeStages()
    {
        return $this->hasMany(Stage::class)->where('is_active', true)->orderBy('sequence');
    }
}
