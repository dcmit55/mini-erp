<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectPurchase extends Model
{
    use SoftDeletes;

    protected $table = 'indo_purchases';

    protected $fillable = [
        'po_number',
        'date',
        'project_type',
        'purchase_type',
        'material_id',
        'new_item_name',
        'quantity',
        'actual_quantity',
        'unit_price',
        'department_id',
        'project_id',
        'internal_project_id',
        'job_order_id',
        'category_id',
        'unit_id',
        'supplier_id',
        'is_offline_order',
        'pic_id',
        'resi_number',
        'total_price',
        'freight',
        'invoice_total',
        'status',
        'item_status',
        'note',
        'finance_notes',
        'checked_at',
        'checked_by',
        'approved_at',
        'approved_by',
        'received_at',
        'received_by',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'integer',
        'actual_quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'freight' => 'decimal:2',
        'invoice_total' => 'decimal:2',
        'checked_at' => 'datetime',
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
        'is_offline_order' => 'boolean',
    ];

    protected $appends = [
        'formatted_date',
        'formatted_unit_price',
        'formatted_total_price',
        'formatted_freight',
        'formatted_invoice_total',
        'formatted_checked_at',
        'formatted_approved_at',
        'formatted_received_at',
        'material_name',
        'status_badge_class',
        'item_status_badge_class',
        'status_text',
        'item_status_text',
        'purchase_type_text',
        'project_type_text',
        'project_name',
        'job_name',
        'is_offline_order_text',
    ];

    // Relationships
    public function material()
    {
        return $this->belongsTo(\App\Models\Logistic\Inventory::class, 'material_id');
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\Admin\Department::class, 'department_id');
    }

    public function project()
    {
        return $this->belongsTo(\App\Models\Production\Project::class, 'project_id');
    }

    public function internalProject()
    {
        return $this->belongsTo(\App\Models\InternalProject::class, 'internal_project_id', 'id');
    }

    public function jobOrder()
    {
        return $this->belongsTo(\App\Models\Production\JobOrder::class, 'job_order_id');
    }

    public function category()
    {
        return $this->belongsTo(\App\Models\Logistic\Category::class, 'category_id');
    }

    public function unit()
    {
        return $this->belongsTo(\App\Models\Logistic\Unit::class, 'unit_id');
    }

    public function supplier()
    {
        return $this->belongsTo(\App\Models\Procurement\Supplier::class, 'supplier_id');
    }

    public function pic()
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'pic_id');
    }

    public function checker()
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'checked_by');
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'approved_by');
    }

    public function receiver()
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'received_by');
    }

    public function dcmCosting()
    {
        return $this->hasOne(\App\Models\Finance\DcmCosting::class, 'purchase_id', 'id');
    }

    // Scopes
    public function scopeRestock($query)
    {
        return $query->where('purchase_type', 'restock');
    }

    public function scopeNewItem($query)
    {
        return $query->where('purchase_type', 'new_item');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeItemPending($query)
    {
        return $query->whereIn('item_status', ['pending_check', 'pending']);
    }

    public function scopeItemMatched($query)
    {
        return $query->where('item_status', 'matched');
    }

    public function scopeItemNotMatched($query)
    {
        return $query->where('item_status', 'not_matched');
    }

    public function scopeItemChecked($query)
    {
        return $query->whereNotNull('checked_at');
    }

    public function scopeItemNotChecked($query)
    {
        return $query->whereNull('checked_at');
    }

    public function scopeOfflineOrder($query)
    {
        return $query->where('is_offline_order', true);
    }

    public function scopeOnlineOrder($query)
    {
        return $query->where('is_offline_order', false);
    }

    public function scopeClientProjects($query)
    {
        return $query->where('project_type', 'client');
    }

    public function scopeInternalProjects($query)
    {
        return $query->where('project_type', 'internal');
    }

    public function scopeWithTracking($query)
    {
        return $query->whereNotNull('resi_number');
    }

    public function scopeWithoutTracking($query)
    {
        return $query->whereNull('resi_number');
    }

    // Accessors
    public function getFormattedInvoiceTotalAttribute()
    {
        return 'Rp ' . number_format($this->invoice_total, 0, ',', '.');
    }

    public function getFormattedUnitPriceAttribute()
    {
        return 'Rp ' . number_format($this->unit_price, 0, ',', '.');
    }

    public function getFormattedTotalPriceAttribute()
    {
        return 'Rp ' . number_format($this->total_price, 0, ',', '.');
    }

    public function getFormattedFreightAttribute()
    {
        return $this->freight ? 'Rp ' . number_format($this->freight, 0, ',', '.') : '-';
    }

    public function getFormattedDateAttribute()
    {
        return $this->date ? $this->date->format('d/m/Y') : '-';
    }

    public function getFormattedCheckedAtAttribute()
    {
        return $this->checked_at ? $this->checked_at->format('d/m/Y H:i') : '-';
    }

    public function getFormattedApprovedAtAttribute()
    {
        return $this->approved_at ? $this->approved_at->format('d/m/Y H:i') : '-';
    }

    public function getFormattedReceivedAtAttribute()
    {
        return $this->received_at ? $this->received_at->format('d/m/Y H:i') : '-';
    }

    public function getProjectTypeTextAttribute()
    {
        $types = [
            'client' => 'Client Project',
            'internal' => 'Internal Project',
        ];
        
        return $types[$this->project_type] ?? 'Unknown';
    }

    public function getProjectNameAttribute()
    {
        if ($this->project_type === 'client' && $this->project) {
            return $this->project->name;
        } elseif ($this->project_type === 'internal' && $this->internalProject) {
            return $this->internalProject->project . ' - ' . $this->internalProject->department;
        }
        
        return 'N/A';
    }

    public function getJobNameAttribute()
    {
        if ($this->project_type === 'client' && $this->jobOrder) {
            return $this->jobOrder->name;
        } elseif ($this->project_type === 'internal' && $this->internalProject) {
            return $this->internalProject->job;
        }
        
        return 'N/A';
    }

    public function getIsOfflineOrderTextAttribute()
    {
        return $this->is_offline_order ? 'Offline Order' : 'Online Order';
    }

    // Business Logic Methods
    public function canEdit()
    {
        return $this->status === 'pending';
    }

    public function canDelete()
    {
        return $this->status === 'pending';
    }

    public function canCheck()
    {
        return $this->status === 'approved' && is_null($this->checked_at);
    }

    public function canApprove()
    {
        return $this->status === 'pending';
    }

    public function canReject()
    {
        return $this->status === 'pending';
    }

    public function canMarkAsReceived()
    {
        return $this->status === 'approved' && in_array($this->item_status, ['pending_check', 'pending']);
    }

    public function canUpdateResi()
    {
        return $this->status === 'approved' && in_array($this->item_status, ['pending_check', 'pending']);
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isItemMatched()
    {
        return $this->item_status === 'matched';
    }

    public function isItemNotMatched()
    {
        return $this->item_status === 'not_matched';
    }

    public function isItemPending()
    {
        return in_array($this->item_status, ['pending_check', 'pending']);
    }

    public function isItemChecked()
    {
        return !is_null($this->checked_at);
    }

    public function isOfflineOrder()
    {
        if (!is_null($this->is_offline_order)) {
            return $this->is_offline_order;
        }
        
        return $this->supplier && strtolower($this->supplier->name) === 'offline order';
    }

    public function isRestock()
    {
        return $this->purchase_type === 'restock';
    }

    public function isNewItem()
    {
        return $this->purchase_type === 'new_item';
    }

    public function isClientProject()
    {
        return $this->project_type === 'client';
    }

    public function isInternalProject()
    {
        return $this->project_type === 'internal';
    }

    public function hasResiNumber()
    {
        return !empty($this->resi_number);
    }

    public function getMaterialNameAttribute()
    {
        if ($this->isRestock() && $this->material) {
            return $this->material->name;
        } elseif ($this->isNewItem()) {
            return $this->new_item_name;
        }
        
        return 'Unknown Material';
    }

    public function getStatusBadgeClassAttribute()
    {
        $classes = [
            'pending' => 'badge bg-warning',
            'approved' => 'badge bg-success',
            'rejected' => 'badge bg-danger',
        ];
        
        return $classes[$this->status] ?? 'badge bg-secondary';
    }

    public function getItemStatusBadgeClassAttribute()
    {
        $classes = [
            'pending_check' => 'badge bg-secondary',
            'pending' => 'badge bg-secondary',
            'matched' => 'badge bg-success',
            'not_matched' => 'badge bg-danger',
        ];
        
        return $classes[$this->item_status] ?? 'badge bg-secondary';
    }

    public function getStatusTextAttribute()
    {
        $statuses = [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];
        
        return $statuses[$this->status] ?? 'Unknown';
    }

    public function getItemStatusTextAttribute()
    {
        $statuses = [
            'pending_check' => 'Pending Check',
            'pending' => 'Pending',
            'matched' => 'Matched',
            'not_matched' => 'Not Matched',
        ];
        
        return $statuses[$this->item_status] ?? 'Unknown';
    }

    public function getPurchaseTypeTextAttribute()
    {
        $types = [
            'restock' => 'Restock',
            'new_item' => 'Item Baru',
        ];
        
        return $types[$this->purchase_type] ?? 'Unknown';
    }

    // Helper Methods
    public function markAsChecked($userId, $status = 'matched')
    {
        $this->checked_at = now();
        $this->checked_by = $userId;
        $this->item_status = $status;
        return $this->save();
    }

    public function markAsApproved($userId)
    {
        $this->status = 'approved';
        $this->approved_at = now();
        $this->approved_by = $userId;
        return $this->save();
    }

    public function markAsRejected($userId)
    {
        $this->status = 'rejected';
        $this->approved_at = now();
        $this->approved_by = $userId;
        return $this->save();
    }

    public function markAsReceived($userId)
    {
        $this->received_at = now();
        $this->received_by = $userId;
        $this->item_status = 'matched';
        return $this->save();
    }

    public function updateResiNumber($resiNumber)
    {
        $this->resi_number = $resiNumber;
        return $this->save();
    }

    public function calculateTotalPrice()
    {
        if ($this->quantity && $this->unit_price) {
            $this->total_price = $this->quantity * $this->unit_price;
        }
        return $this->total_price;
    }

    public function calculateInvoiceTotal()
    {
        $this->invoice_total = ($this->total_price ?? 0) + ($this->freight ?? 0);
        return $this->invoice_total;
    }

    // Boot Method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($purchase) {
            // Generate PO number jika kosong
            if (empty($purchase->po_number)) {
                $purchase->po_number = 'PO-' . date('ymd') . str_pad(static::count() + 1, 3, '0', STR_PAD_LEFT);
            }
        });

        static::saving(function ($purchase) {
            // Auto-calculate total_price
            if ($purchase->quantity && $purchase->unit_price) {
                $purchase->total_price = $purchase->quantity * $purchase->unit_price;
            }
            
            // Auto-calculate invoice_total
            if ($purchase->total_price) {
                $purchase->invoice_total = $purchase->total_price + ($purchase->freight ?? 0);
            }
            
            // Set offline order flag
            if ($purchase->supplier && strtolower($purchase->supplier->name) === 'offline order') {
                $purchase->is_offline_order = true;
            }
            
            // Set PIC jika kosong dan user sedang login
            if (!$purchase->pic_id && auth()->check()) {
                $purchase->pic_id = auth()->id();
            }
            
            // Set default item_status sesuai dengan database
            if (!$purchase->item_status) {
                $purchase->item_status = 'pending_check'; // Default dari database
            }
            
            // Set default status
            if (!$purchase->status) {
                $purchase->status = 'pending';
            }
            
            // Set default project_type
            if (!$purchase->project_type) {
                $purchase->project_type = 'client';
            }
            
            // Set default purchase_type
            if (!$purchase->purchase_type) {
                $purchase->purchase_type = 'restock';
            }
        });
    }
}