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

    protected $fillable = ['department_id', 'movement_date', 'movement_type', 'movement_type_value', 'origin', 'destination', 'sender', 'receiver', 'status', 'sender_status', 'receiver_status', 'notes', 'created_by'];

    protected $casts = [
        'movement_date' => 'date',
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
