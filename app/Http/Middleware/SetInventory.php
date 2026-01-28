<?php
// filepath: c:\xampp\htdocs\inventory-system\app\Http\Middleware\SetInventory.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Logistic\Inventory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SetInventory
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    public function handle(Request $request, Closure $next)
    {
        if (!$request->session()->has('inventory_id')) {
            $inventory = Cache::remember('default_inventory', 60, function () {
                return Inventory::first();
            });

            if ($inventory) {
                $request->session()->put('inventory_id', $inventory->id);
            }
        }

        return $next($request);
    }
}
