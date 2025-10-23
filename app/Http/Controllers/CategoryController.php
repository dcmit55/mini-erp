<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        // Block admin visitor
        if (Auth::user()->isReadOnlyAdmin()) {
            if ($request->ajax()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'You do not have permission to create categories.',
                    ],
                    403,
                );
            }
            abort(403, 'You do not have permission to create categories.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
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
        $category = Category::create(['name' => $request->name]);
        return response()->json($category);
    }

    public function json()
    {
        // return Category::select('id', 'name')->get();
        return response()->json(Category::select('id', 'name')->get());
    }
}
