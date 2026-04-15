<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\ViolationCategory;
use Illuminate\Http\Request;

class ViolationCategoryController extends Controller
{
    public function index()
    {
        $categories = ViolationCategory::orderBy('severity')->orderBy('name')->get();
        return view('hr.violation-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'           => 'required|string|max:20|unique:violation_categories,code',
            'name'           => 'required|string|max:100',
            'severity'       => 'required|in:low,medium,high,critical',
            'can_bulk_issue' => 'boolean',
        ]);

        ViolationCategory::create(array_merge($validated, [
            'can_bulk_issue' => $request->boolean('can_bulk_issue'),
            'is_active'      => true,
        ]));

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, ViolationCategory $violationCategory)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:100',
            'severity'       => 'required|in:low,medium,high,critical',
            'can_bulk_issue' => 'boolean',
            'is_active'      => 'boolean',
        ]);

        $violationCategory->update(array_merge($validated, [
            'can_bulk_issue' => $request->boolean('can_bulk_issue'),
            'is_active'      => $request->boolean('is_active'),
        ]));

        return back()->with('success', 'Kategori diperbarui.');
    }

    public function destroy(ViolationCategory $violationCategory)
    {
        $violationCategory->update(['is_active' => false]);
        return back()->with('success', 'Kategori dinonaktifkan.');
    }
}
