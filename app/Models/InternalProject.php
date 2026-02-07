<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Admin\User;

class InternalProject extends Model
{
    use HasFactory;

    protected $table = 'internal_projects';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    
    public $timestamps = false; // Karena kita pakai created_at manual
    
    protected $fillable = [
        'project',      // project type
        'job',          // job singkat
        'description',  // deskripsi lengkap (TAMBAHAN)
        'department',
        'pic',
        'update_by'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $datePart = date('dmy');
            
            $lastProject = self::where('id', 'like', 'IP-' . $datePart . '-%')
                ->orderBy('id', 'desc')
                ->first();
            
            if ($lastProject) {
                $lastId = $lastProject->id;
                $lastNumber = (int) substr($lastId, strrpos($lastId, '-') + 1);
                $sequence = $lastNumber + 1;
            } else {
                $sequence = 1;
            }
            
            $model->id = 'IP-' . $datePart . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
            
            if (empty($model->uid)) {
                $model->uid = Str::uuid();
            }
            
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
            
            if (empty($model->department)) {
                $model->department = 'PT DCM';
            }
            
            if (empty($model->update_by) && !empty($model->pic)) {
                $model->update_by = $model->pic;
            }
        });
    }

    public function picUser()
    {
        return $this->belongsTo(User::class, 'pic');
    }

    public function updateUser()
    {
        return $this->belongsTo(User::class, 'update_by');
    }

    public function getPicUsernameAttribute()
    {
        return $this->picUser->username ?? 'N/A';
    }

    public function getUpdateByUsernameAttribute()
    {
        return $this->updateUser->username ?? 'N/A';
    }

    public function getFormattedCreatedAtAttribute()
    {
        try {
            if ($this->created_at instanceof \Illuminate\Support\Carbon) {
                return $this->created_at->format('d/m/Y');
            }
            
            if (is_string($this->created_at)) {
                return \Carbon\Carbon::parse($this->created_at)->format('d/m/Y');
            }
            
            return 'N/A';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    public function getProjectBadgeClassAttribute()
    {
        return match($this->project) {
            'Office' => 'bg-primary',
            'Machine' => 'bg-info',
            'Testing' => 'bg-warning',
            'Facilities' => 'bg-success',
            default => 'bg-secondary'
        };
    }

    public function getProjectBadgeIconAttribute()
    {
        return match($this->project) {
            'Office' => 'fa-building',
            'Machine' => 'fa-cogs',
            'Testing' => 'fa-flask',
            'Facilities' => 'fa-tools',
            default => 'fa-project-diagram'
        };
    }

    /**
     * Get short description (untuk display di table)
     */
    public function getShortDescriptionAttribute()
    {
        if ($this->description) {
            return Str::limit($this->description, 80);
        }
        
        // Fallback ke job jika description kosong
        return Str::limit($this->job, 80);
    }
}