<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class GoodsOutExport implements FromView
{
    protected $goodsOuts;

    public function __construct($goodsOuts)
    {
        $this->goodsOuts = $goodsOuts;
    }

    public function view(): View
    {
        return view('logistic.goods_out.export', [
            'goodsOuts' => $this->goodsOuts
        ]);
    }
}
