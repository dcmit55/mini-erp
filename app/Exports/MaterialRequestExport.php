<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class MaterialRequestExport implements FromView
{
    protected $requests;

    public function __construct($requests)
    {
        $this->requests = $requests;
    }

    public function view(): View
    {
        return view('logistic.material_requests.export', [
            'requests' => $this->requests->map(function ($req) {
                $req->remaining_qty = $req->qty - $req->processed_qty;
                return $req;
            }),
        ]);
    }
}
