<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Support\Facades\Auth;

class InventoryExport implements FromView
{
    protected $inventories;

    public function __construct($inventories)
    {
        $this->inventories = $inventories;
    }

    public function view(): View
    {
        $userRole = Auth::user()->role;
        $allowedRoles = ['super_admin', 'admin_logistic', 'admin_finance'];

        return view('logistic.inventory.export', [
            'inventories' => $this->inventories,
            'showCurrencyAndPrice' => in_array($userRole, $allowedRoles), // Kirim flag ke view
        ]);
    }
}
