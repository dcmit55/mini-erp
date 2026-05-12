<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\User;

class CompanyHoliday extends Model
{
    protected $fillable = ['date', 'name', 'type', 'notes', 'created_by'];

    protected $casts = [
        'date' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            'paid_leave_deduction' => 'Potong Cuti',
            'unpaid'               => 'Unpaid',
            default                => 'Libur (Gratis)',
        };
    }
}
