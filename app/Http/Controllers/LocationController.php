<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function store(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            if ($request->ajax()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'You do not have permission to create locations.',
                    ],
                    403,
                );
            }
            abort(403, 'You do not have permission to create locations.');
        }

        $request->validate(['name' => 'required|string|max:255|unique:locations,name']);
        $location = Location::create(['name' => $request->name]);
        return response()->json($location);
    }
}
