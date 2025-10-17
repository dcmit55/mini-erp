<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CurrencyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $rolesAllowed = ['super_admin', 'admin_finance', 'admin_logistic', 'admin'];
            if (!in_array(Auth::user()->role, $rolesAllowed)) {
                abort(403, 'Unauthorized');
            }
            return $next($request);
        });

        // Batasi create/edit/delete
        $this->middleware(function ($request, $next) {
            if (Auth::user()->isReadOnlyAdmin()) {
                abort(403, 'You do not have permission to modify currency data.');
            }
            return $next($request);
        })->only(['store', 'update', 'destroy']);
    }

    public function index()
    {
        $currencies = Currency::all(); // Ambil semua currency dari database
        return view('currency.index', compact('currencies'));
    }

    public function store(Request $request)
    {
        $request->merge(['name' => trim($request->name)]);
        $request->validate([
            'name' => 'required|string|max:255',
            'exchange_rate' => 'nullable|numeric',
        ]);

        // Cek currency dengan nama sama, case-insensitive, termasuk yang soft deleted
        $existing = Currency::withTrashed()
            ->whereRaw('LOWER(name) = ?', [strtolower($request->name)])
            ->first();
        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
                $existing->exchange_rate = $request->exchange_rate;
                $existing->save();
                if ($request->ajax()) {
                    return response()->json([
                        'success' => "Currency '{$existing->name}' restored successfully.",
                        'id' => $existing->id,
                        'name' => $existing->name,
                    ]);
                }
                return back()->with('success', "Currency <b>{$existing->name}</b> restored successfully.");
            } else {
                $msg = "Currency '{$request->name}' already exists.";
                if ($request->ajax()) {
                    return response()->json(['message' => $msg], 422);
                }
                return back()
                    ->withErrors(['name' => $msg])
                    ->withInput();
            }
        }

        // Jika belum ada, buat baru
        $currency = Currency::create($request->only('name', 'exchange_rate'));
        if ($request->ajax()) {
            return response()->json([
                'success' => "Currency '{$currency->name}' added successfully.",
                'id' => $currency->id,
                'name' => $currency->name,
            ]);
        }
        return back()->with('success', "Currency <b>{$currency->name}</b> added successfully.");
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:currencies,name,' . $id . ',id,deleted_at,NULL',
            'exchange_rate' => 'nullable|numeric',
        ]);

        $currency = Currency::findOrFail($id);
        $currency->update($request->all());

        return back()->with('success', "Currency <b>{$currency->name}</b> updated successfully.");
    }

    public function destroy($id)
    {
        $currency = Currency::findOrFail($id);
        $currencyName = $currency->name;
        $currency->delete();

        return back()->with('success', "Currency <b>{$currencyName}</b> deleted successfully.");
    }
}
