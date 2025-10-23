<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255|unique:suppliers,name']);
        $supplier = Supplier::create(['name' => $request->name]);
        return response()->json($supplier);
    }
}
