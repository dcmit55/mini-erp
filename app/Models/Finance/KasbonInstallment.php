<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\User;

class KasbonInstallment extends Model
{
    protected $fillable = [
        'kasbon_id',
        'bulan_ke',
        'due_date',
        'jumlah_cicilan',
        'jumlah_pokok',
        'jumlah_bunga',
        'jumlah_biaya_admin',
        'jumlah_dibayar',
        'pokok_paid_at',
        'pokok_confirmed_by',
        'cash_paid_at',
        'cash_received_by',
        'status',
        'metode',
        'paid_at',
        'created_by',
        'note',
    ];

    protected $casts = [
        'due_date'       => 'date',
        'paid_at'        => 'datetime',
        'jumlah_cicilan'      => 'decimal:2',
        'jumlah_pokok'        => 'decimal:2',
        'jumlah_bunga'        => 'decimal:2',
        'jumlah_biaya_admin'  => 'decimal:2',
        'jumlah_dibayar'      => 'decimal:2',
        'pokok_paid_at'       => 'datetime',
        'cash_paid_at'        => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID    = 'paid';
    public const STATUS_PARTIAL = 'partial';

    public const METODE_CASH             = 'cash';
    public const METODE_PAYROLL_DEDUCTION = 'payroll_deduction';

    public function kasbon()
    {
        return $this->belongsTo(KasbonRequest::class, 'kasbon_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSisaAttribute(): float
    {
        return (float) ($this->jumlah_cicilan - $this->jumlah_dibayar);
    }
}
