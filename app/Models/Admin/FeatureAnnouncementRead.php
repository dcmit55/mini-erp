<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FeatureAnnouncementRead extends Model
{
    use HasFactory;

    protected $fillable = ['announcement_id', 'user_id', 'read_at'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Relationship ke announcement
     */
    public function announcement()
    {
        return $this->belongsTo(FeatureAnnouncement::class, 'announcement_id');
    }

    /**
     * Relationship ke user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
