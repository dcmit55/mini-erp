<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Production\JobOrder;
use App\Models\Production\Project;
use App\Models\Admin\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobOrderController extends Controller
{
    // INDEX - Tampilkan semua job order dengan filter
    public function index(Request $request)
    {
        // Get filter data untuk dropdown
        $projects = Project::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('name')->get(['id', 'name']);
        
        // Build query
        $query = JobOrder::with(['project', 'department', 'creator']);
        
        // Apply filters
        if ($request->filled('project_filter')) {
            $query->where('project_id', $request->project_filter);
        }
        
        if ($request->filled('department_filter')) {
            $query->where('department_id', $request->department_filter);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }
        
        // Order and paginate
        $jobOrders = $query->latest()->paginate(20)->withQueryString();
        
        return view('production.job-orders.index', compact(
            'jobOrders',
            'projects',
            'departments'
        ));
    }

    // CREATE - Form tambah job order
    public function create()
    {
        $projects = Project::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('name')->get(['id', 'name']);
        
        return view('production.job-orders.create', compact('projects', 'departments'));
    }

    // STORE - Simpan job order baru
    public function store(Request $request)
    {
        // Validasi
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'department_id' => 'required|exists:departments,id',
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
        $jobOrder = JobOrder::with(['project', 'department', 'creator'])
            ->findOrFail($id);
        return view('production.job-orders.show', compact('jobOrder'));
    }

    // EDIT - Form edit
    public function edit($id)
    {
        $jobOrder = JobOrder::findOrFail($id);
        $projects = Project::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('name')->get(['id', 'name']);
        
        return view('production.job-orders.edit', compact('jobOrder', 'projects', 'departments'));
    }

    // UPDATE - Update job order
    public function update(Request $request, $id)
    {
        $jobOrder = JobOrder::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'department_id' => 'required|exists:departments,id',
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
        $jobOrder->delete();
        
        return redirect()->route('production.job-orders.index')
            ->with('success', 'Job Order berhasil dihapus: ' . $jobId);
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