<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class TimingExport implements FromView
{
    protected $timings;

    public function __construct($timings)
    {
        $this->timings = $timings;
    }

    public function view(): View
    {
        return view('production.timings.export', [
            'timings' => $this->timings,
        ]);
    }
}
