<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class ViolationLog extends Model
{
    protected $table = 'violation_log';

    protected $fillable = [
        'employee_id', 'violation_cat_id', 'violation_date',
        'source', 'warning_letter_id', 'batch_id', 'notes',
    ];

    protected $casts = [
        'violation_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function violationCategory()
    {
        return $this->belongsTo(ViolationCategory::class, 'violation_cat_id');
    }

    public function warningLetter()
    {
        return $this->belongsTo(WarningLetter::class);
    }
}
