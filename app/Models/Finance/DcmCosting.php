<?php

namespace App\Models\Finance;

use App\Models\Procurement\ProjectPurchase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DcmCosting extends Model
{
    use SoftDeletes;

    protected $table = 'dcm_costings';

    protected $fillable = [
        'uid',
        'po_number',
        'date',
        'purchase_type',
        'item_name',
        'quantity',
        'unit_price',
        'total_price',
        'freight',
        'invoice_total',
        'department',
        'project_type',
        'project_name',
        'job_order',
        'supplier',
        'tracking_number',
        'resi_number',
        'status',
        'item_status',
        'finance_notes',
        'approved_at',
        'purchase_id'
    ];

    protected $casts = [
        'uid' => 'string',
        'date' => 'date',
        'approved_at' => 'datetime',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'freight' => 'decimal:2',
        'invoice_total' => 'decimal:2',
    ];

    /**
     * Boot method untuk generate UUID
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = Str::uuid();
            }
        });
    }

    /**
     * Relationship dengan ProjectPurchase
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(ProjectPurchase::class, 'purchase_id');
    }
    
    /**
     * Scope untuk mencari berdasarkan uid
     */
    public function scopeByUid($query, $uid)
    {
        return $query->where('uid', $uid);
    }
    
    /**
     * Scope untuk status approved
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
    
    /**
     * Scope untuk status pending
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    
    /**
     * Scope untuk status rejected
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
    
    /**
     * Scope untuk filter berdasarkan tanggal
     */
    public function scopeDateBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
    
    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'uid';
    }
    
    /**
     * Format currency
     */
    public function formatCurrency($value)
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }
    
    /**
     * Get formatted total price
     */
    public function getFormattedTotalPriceAttribute()
    {
        return $this->formatCurrency($this->total_price);
    }
    
    /**
     * Get formatted invoice total
     */
    public function getFormattedInvoiceTotalAttribute()
    {
        return $this->formatCurrency($this->invoice_total);
    }
    
    /**
     * Get formatted unit price
     */
    public function getFormattedUnitPriceAttribute()
    {
        return $this->formatCurrency($this->unit_price);
    }
    
    /**
     * Get formatted freight
     */
    public function getFormattedFreightAttribute()
    {
        return $this->formatCurrency($this->freight);
    }
    
    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        return [
            'pending' => 'badge bg-warning',
            'approved' => 'badge bg-success',
            'rejected' => 'badge bg-danger',
        ][$this->status] ?? 'badge bg-secondary';
    }
    
    /**
     * Get item status badge class
     */
    public function getItemStatusBadgeClassAttribute()
    {
        return [
            'pending' => 'badge bg-secondary',
            'received' => 'badge bg-success',
            'not_received' => 'badge bg-danger',
        ][$this->item_status] ?? 'badge bg-secondary';
    }
    
    /**
     * Get formatted date
     */
    public function getFormattedDateAttribute()
    {
        return $this->date->format('d/m/Y');
    }
    
    /**
     * Get formatted approved at
     */
    public function getFormattedApprovedAtAttribute()
    {
        return $this->approved_at ? $this->approved_at->format('d/m/Y H:i') : '-';
    }
}