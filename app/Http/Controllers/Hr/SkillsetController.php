<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Skillset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SkillsetController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Quick add skillset (AJAX)
     */
    public function store(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'You do not have permission to add skillsets.',
                ],
                403,
            );
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:skillsets,name',
            'category' => 'nullable|string|max:255',
            'proficiency_required' => 'required|in:basic,intermediate,advanced',
            'description' => 'nullable|string',
        ]);

        try {
            $skillset = Skillset::create([
                'name' => $request->name,
                'category' => $request->category,
                'proficiency_required' => $request->proficiency_required,
                'description' => $request->description,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Skillset added successfully!',
                'skillset' => $skillset,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to add skillset: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Get all active skillsets (AJAX)
     */
    public function json()
    {
        try {
            $skillsets = Skillset::active()->orderBy('name')->get();
            return response()->json($skillsets);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to load skillsets',
                ],
                500,
            );
        }
    }

    /**
     * Search skillsets (for Select2)
     */
    public function search(Request $request)
    {
        $term = $request->input('q');

        $skillsets = Skillset::active()
            ->when($term, function ($query) use ($term) {
                return $query->where('name', 'like', "%{$term}%")->orWhere('category', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->get(['id', 'name', 'category']);

        return response()->json($skillsets);
    }
}
