<?php

namespace App\Http\Controllers\Logistic;

use App\Http\Controllers\Controller;
use App\Models\Logistic\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:logistic.inventory.view');
        $this->middleware('can:logistic.inventory.create')->only(['store']);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255|unique:locations,name']);
        $location = Location::create(['name' => $request->name]);
        return response()->json($location);
    }
}
