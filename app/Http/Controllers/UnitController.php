<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Unit;

class UnitController extends Controller
{
    public function store(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            if ($request->ajax()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'You do not have permission to create units.',
                    ],
                    403,
                );
            }
            abort(403, 'You do not have permission to create units.');
        }

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
