<?php

namespace App\Http\Controllers\Logistic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Logistic\Unit;
use Illuminate\Support\Facades\Auth;

class UnitController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:logistic.inventory.view');
        $this->middleware('can:logistic.inventory.create')->only(['store']);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:units,name',
        ]);
        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => $validator->errors()->first(),
                    ],
                    422,
                );
            }
            return back()->withErrors($validator)->withInput();
        }
        $unit = Unit::create(['name' => $request->name]);
        return response()->json($unit);
    }

    public function json()
    {
        // return Unit::select('id', 'name')->orderBy('name')->get();
        return response()->json(Unit::select('id', 'name')->orderBy('name')->get());
    }
}
