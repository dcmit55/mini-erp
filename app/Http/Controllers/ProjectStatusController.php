<?php
namespace App\Http\Controllers;

use App\Models\ProjectStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectStatusController extends Controller
{
    public function store(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            if ($request->ajax()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'You do not have permission to create project status.',
                    ],
                    403,
                );
            }
            return back()->with('error', 'You do not have permission to create project status.');
        }

        $request->validate(['name' => 'required|string|max:255|unique:project_statuses,name']);
        $status = ProjectStatus::create(['name' => $request->name]);
        if ($request->ajax()) {
            return response()->json($status);
        }
        return back()->with('success', 'Status added!');
    }
}
