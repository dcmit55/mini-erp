<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Production\JobOrder;
use App\Models\Production\Project;
use App\Models\Admin\Department;
use App\Models\Admin\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobOrderController extends Controller
{
    // INDEX - Tampilkan semua job order
    public function index(Request $request)
    {
        $search = $request->search;
        
        $jobOrders = JobOrder::with(['project', 'department', 'assignee', 'creator'])
            ->when($search, function($query) use ($search) {
                return $query->where('id', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(20);
            
        return view('production.job-orders.index', compact('jobOrders'));
    }

    // CREATE - Form tambah job order
    public function create()
    {
        $projects = Project::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('name')->get(['id', 'name']);
        $users = User::orderBy('username')->get(['id', 'username']);
        
        return view('production.job-orders.create', compact('projects', 'departments', 'users'));
    }

    // STORE - Simpan job order baru
    public function store(Request $request)
    {
        // Validasi
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'department_id' => 'required|exists:departments,id',
            'assigned_to' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);
        
        // ID sudah otomatis digenerate oleh model
        $validated['created_by'] = Auth::id();
        
        // Simpan
        $jobOrder = JobOrder::create($validated);
        
        return redirect()->route('production.job-orders.index')
            ->with('success', 'Job Order berhasil dibuat: ' . $jobOrder->id);
    }

    // SHOW - Tampilkan detail
    public function show($id)
    {
        $jobOrder = JobOrder::with(['project', 'department', 'assignee', 'creator'])
            ->findOrFail($id);
        return view('production.job-orders.show', compact('jobOrder'));
    }

    // EDIT - Form edit
    public function edit($id)
    {
        $jobOrder = JobOrder::findOrFail($id);
        $projects = Project::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('name')->get(['id', 'name']);
        $users = User::orderBy('username')->get(['id', 'username']);
        
        return view('production.job-orders.edit', compact('jobOrder', 'projects', 'departments', 'users'));
    }

    // UPDATE - Update job order
    public function update(Request $request, $id)
    {
        $jobOrder = JobOrder::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'department_id' => 'required|exists:departments,id',
            'assigned_to' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);
        
        $jobOrder->update($validated);
        
        return redirect()->route('production.job-orders.index')
            ->with('success', 'Job Order berhasil diperbarui: ' . $jobOrder->id);
    }

    // DESTROY - Hapus PERMANEN job order
    public function destroy($id)
    {
        $jobOrder = JobOrder::findOrFail($id);
        $jobId = $jobOrder->id;
        $jobOrder->delete(); // DELETE PERMANEN dari database
        
        return redirect()->route('production.job-orders.index')
            ->with('success', 'Job Order berhasil dihapus : ' . $jobId);
    }

    // API untuk get job orders by project (untuk dropdown)
    public function getByProject($projectId)
    {
        $jobOrders = JobOrder::where('project_id', $projectId)
            ->orderBy('name')
            ->get(['id', 'name', 'start_date', 'end_date']);
            
        return response()->json($jobOrders);
    }
}