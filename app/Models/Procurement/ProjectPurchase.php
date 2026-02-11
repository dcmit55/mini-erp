<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

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
        'revision_at',
        'is_current',
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
        'revision_at' => 'datetime',
        'is_offline_order' => 'boolean',
        'is_current' => 'boolean',
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
        'formatted_revision_at',
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
        'has_revisions',
        'is_latest_revision',
        'revision_number',
        'total_revisions',
        'revision_info',
        'pic_username',
        'checker_username',
        'approver_username',
        'receiver_username',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================
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

    // ============================================
    // SCOPES
    // ============================================
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeByPoNumber($query, $poNumber)
    {
        return $query->where('po_number', $poNumber)
                     ->orderBy('created_at', 'desc');
    }

    public function scopeRevisionsOnly($query)
    {
        return $query->where('is_current', false);
    }

    public function scopeLatestRevision($query)
    {
        return $query->where('is_current', true);
    }

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

    // ============================================
    // ACCESSORS
    // ============================================
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

    public function getFormattedRevisionAtAttribute()
    {
        return $this->revision_at ? $this->revision_at->format('d/m/Y H:i') : '-';
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

    public function getHasRevisionsAttribute()
    {
        if (!$this->po_number) {
            return false;
        }
        
        return self::where('po_number', $this->po_number)
            ->where('id', '!=', $this->id)
            ->exists();
    }

    public function getIsLatestRevisionAttribute()
    {
        return $this->is_current == true;
    }

    public function getRevisionNumberAttribute()
    {
        if (!$this->po_number) {
            return 1;
        }
        
        return self::where('po_number', $this->po_number)
               ->where('created_at', '<=', $this->created_at)
               ->count();
    }

    public function getTotalRevisionsAttribute()
    {
        if (!$this->po_number) {
            return 1;
        }
        
        return self::where('po_number', $this->po_number)->count();
    }

    public function getRevisionInfoAttribute()
    {
        return [
            'is_current' => $this->is_current,
            'revision_number' => $this->revision_number,
            'total_revisions' => $this->total_revisions,
            'is_first' => $this->isFirstRevision(),
            'is_last' => $this->isLastRevision(),
            'revision_at' => $this->revision_at,
            'has_previous' => $this->getPreviousRevision() !== null,
            'has_next' => $this->getNextRevision() !== null,
        ];
    }

    public function getPicUsernameAttribute()
    {
        return $this->pic ? $this->pic->username : 'N/A';
    }

    public function getCheckerUsernameAttribute()
    {
        return $this->checker ? $this->checker->username : 'N/A';
    }

    public function getApproverUsernameAttribute()
    {
        return $this->approver ? $this->approver->username : 'N/A';
    }

    public function getReceiverUsernameAttribute()
    {
        return $this->receiver ? $this->receiver->username : 'N/A';
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

    // ============================================
    // BUSINESS LOGIC METHODS
    // ============================================
    public function canEdit()
    {
        return $this->is_current && ($this->status === 'pending' || $this->status === 'approved');
    }

    public function canDelete()
    {
        return $this->is_current && $this->status === 'pending';
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

    // ============================================
    // REVISION METHODS
    // ============================================
    public function getPreviousRevision()
    {
        return self::where('po_number', $this->po_number)
                   ->where('created_at', '<', $this->created_at)
                   ->orderBy('created_at', 'desc')
                   ->first();
    }

    public function getNextRevision()
    {
        return self::where('po_number', $this->po_number)
                   ->where('created_at', '>', $this->created_at)
                   ->orderBy('created_at', 'asc')
                   ->first();
    }

    public function getAllRevisions()
    {
        return self::where('po_number', $this->po_number)
                   ->orderBy('created_at', 'desc')
                   ->get();
    }

    public function isFirstRevision()
    {
        $first = self::where('po_number', $this->po_number)
                    ->orderBy('created_at', 'asc')
                    ->first();
                    
        return $first && $first->id == $this->id;
    }

    public function isLastRevision()
    {
        return $this->is_current == true;
    }

    // ============================================
    // HELPER METHODS
    // ============================================
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

    // ============================================
    // BOOT METHOD
    // ============================================
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($purchase) {
            if (empty($purchase->po_number)) {
                $purchase->po_number = app(\App\Services\ProjectPurchaseService::class)->generatePONumber();
            }

            $purchase->is_current = true;
            
            if (!$purchase->revision_at) {
                $purchase->revision_at = null;
            }
        });

        static::saving(function ($purchase) {
            if ($purchase->quantity && $purchase->unit_price) {
                $purchase->total_price = $purchase->quantity * $purchase->unit_price;
            }
            
            if ($purchase->total_price) {
                $purchase->invoice_total = $purchase->total_price + ($purchase->freight ?? 0);
            }
            
            if ($purchase->exists && $purchase->isDirty('is_current') && 
                $purchase->getOriginal('is_current') == true && 
                $purchase->is_current == false) {
                
                $purchase->revision_at = now();
            }
            
            if (!$purchase->pic_id && auth()->check()) {
                $purchase->pic_id = auth()->id();
            }
            
            if (!$purchase->item_status) {
                $purchase->item_status = 'pending_check';
            }
            
            if (!$purchase->status) {
                $purchase->status = 'pending';
            }
            
            if (!$purchase->project_type) {
                $purchase->project_type = 'client';
            }
            
            if (!$purchase->purchase_type) {
                $purchase->purchase_type = 'restock';
            }
            
            if (!is_bool($purchase->is_offline_order)) {
                $purchase->is_offline_order = (bool) $purchase->is_offline_order;
            }
        });
        
        static::created(function ($purchase) {
            if ($purchase->is_current) {
                self::where('po_number', $purchase->po_number)
                    ->where('id', '!=', $purchase->id)
                    ->update(['is_current' => 0]);
            }
        });
    }
}