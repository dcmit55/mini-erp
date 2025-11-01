<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class GoodsInExport implements FromView
{
    protected $goodsIns;

    public function __construct($goodsIns)
    {
        $this->goodsIns = $goodsIns;
    }

    public function view(): View
    {
        return view('logistic.goods_in.export', [
            'goodsIns' => $this->goodsIns
        ]);
    }
}
