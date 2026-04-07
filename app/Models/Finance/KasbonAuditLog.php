<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\User;

class KasbonAuditLog extends Model
{
    protected $table = 'kasbon_audit_log';

    public $timestamps = false;

    protected $fillable = [
        'kasbon_id',
        'action',
        'from_status',
        'to_status',
        'actor_id',
        'actor_type',
        'note',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function kasbon()
    {
        return $this->belongsTo(KasbonRequest::class, 'kasbon_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
