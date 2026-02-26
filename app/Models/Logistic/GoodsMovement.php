<?php

namespace App\Models\Logistic;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Logistic\GoodsMovementItem;
use App\Models\Admin\Department;
use App\Models\Admin\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsMovement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'department_id', 
        'movement_date', 
        'movement_type', 
        'movement_type_value', 
        'origin', 
        'destination', 
        'sender', 
        'receiver', 
        'status', 
        'sender_status', 
        'receiver_status', 
        'notes', 
        'created_by',
        // Production Lark integration fields
        'courier_id_sg_bt',
        'courier_id_bt_sg',
        'lark_movement_type',
        'transport_cost',
        'baggage_cost',
        'gst_cost',
        'qty_total',
        'cost_per_item',
        'lark_record_id_sg_bt',
        'lark_record_id_bt_sg',
        'lark_sync_source',
        'lark_synced_at',
        'lark_sync_status',
        'lark_raw_data',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'transport_cost' => 'decimal:2',
        'baggage_cost' => 'decimal:2',
        'gst_cost' => 'decimal:2',
        'qty_total' => 'decimal:2',
        'cost_per_item' => 'decimal:2',
        'lark_synced_at' => 'datetime',
        'lark_raw_data' => 'array',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function items()
    {
        return $this->hasMany(GoodsMovementItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public static function getMovementTypeValues($type)
    {
        $values = [
            'Handcarry' => ['Basuki', 'Jarot', 'Personel Lainnya'],
            'Courier' => ['C & G Express', 'PT Tirta Mandiri Sukses', 'Sindo Makmur Sentosa', 'SMS Logistic', 'Soon Brother', 'Harasoon'],
        ];

        return $values[$type] ?? [];
    }
}
