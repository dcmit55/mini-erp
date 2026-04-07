<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use App\Models\Hr\Employee;
use App\Models\Admin\User;

class KasbonRequest extends Model
{
    protected $fillable = [
        'ref_number',
        'employee_id',
        'nama_lengkap',
        'nik_karyawan',
        'department_id',
        'no_wa',
        'jumlah_diminta',
        'jumlah_disetujui',
        'tenor_bulan',
        'suku_bunga_persen',
        'biaya_admin',
        'alasan',
        'dokumen_url',
        'status',
        'token',
        'ip_address',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'catatan_admin',
        'disbursed_at',
        'settled_at',
    ];

    protected $casts = [
        'submitted_at'  => 'datetime',
        'reviewed_at'   => 'datetime',
        'disbursed_at'  => 'datetime',
        'settled_at'    => 'datetime',
        'jumlah_diminta'    => 'decimal:2',
        'jumlah_disetujui'  => 'decimal:2',
        'suku_bunga_persen' => 'decimal:2',
        'biaya_admin'       => 'decimal:2',
    ];

    public const STATUS_PENDING     = 'pending';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED    = 'approved';
    public const STATUS_REJECTED    = 'rejected';
    public const STATUS_DISBURSED   = 'disbursed';
    public const STATUS_REPAYING    = 'repaying';
    public const STATUS_SETTLED     = 'settled';

    public const ACTIVE_STATUSES = [
        self::STATUS_APPROVED,
        self::STATUS_DISBURSED,
        self::STATUS_REPAYING,
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\Admin\Department::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function installments()
    {
        return $this->hasMany(KasbonInstallment::class, 'kasbon_id')->orderBy('bulan_ke');
    }

    public function auditLogs()
    {
        return $this->hasMany(KasbonAuditLog::class, 'kasbon_id')->orderBy('created_at');
    }

    public function limit()
    {
        return $this->hasOneThrough(
            KasbonLimit::class,
            \App\Models\Admin\Department::class,
            'id',
            'department_id',
            'department_id',
            'id'
        );
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->installments->sum('jumlah_dibayar');
    }

    public function getSisaTagihanAttribute(): float
    {
        return (float) ($this->jumlah_disetujui - $this->total_paid);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES);
    }
}
