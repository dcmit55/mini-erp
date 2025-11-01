<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Production\Project;
use App\Models\Logistic\Inventory;
use App\Models\Logistic\Unit;
use App\Models\Production\MaterialPlanning;
use App\Models\Admin\Department;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Procurement\PurchaseRequest;

class MaterialPlanningController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        // Buat query dasar dengan relasi yang diperlukan
        $query = MaterialPlanning::with(['project.department', 'unit', 'requester']);

        // Apply filter berdasarkan department jika ada
        if ($request->filled('department_filter')) {
            $query->whereHas('project', function ($q) use ($request) {
                $q->where('department_id', $request->department_filter);
            });
        }

        // Apply filter berdasarkan project jika ada
        if ($request->filled('project_filter')) {
            $query->where('project_id', $request->project_filter);
        }

        // Apply filter berdasarkan tanggal jika ada
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Apply filter berdasarkan order type jika ada
        if ($request->filled('order_type_filter')) {
            $query->where('order_type', $request->order_type_filter);
        }

        $plannings = $query->orderBy('eta_date')->get()->groupBy('project_id');

        // Dapatkan data projects dengan department
        $projects = Project::with('department')->whereIn('id', $plannings->keys())->get()->keyBy('id');

        // Dapatkan semua departments untuk filter
        $departments = Department::orderBy('name')->get();

        // Dapatkan semua projects untuk filter
        $allProjects = Project::with('department')->orderBy('name')->get();

        // Hitung created date dan last update untuk setiap project
        $projectStats = [];
        foreach ($plannings as $projectId => $plans) {
            $createdDates = $plans->pluck('created_at');
            $updatedDates = $plans->pluck('updated_at');

            $projectStats[$projectId] = [
                'created_date' => $createdDates->min(), // Tanggal pertama kali dibuat
                'last_update' => $updatedDates->max(), // Tanggal terakhir diupdate
            ];
        }

        return view('production.material_planning.index', compact('plannings', 'projects', 'departments', 'allProjects', 'projectStats'));
    }

    public function create()
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to create material planning.');
        }

        return view('production.material_planning.create', [
            'projects' => \App\Models\Production\Project::orderBy('name')->get(),
            'inventories' => \App\Models\Logistic\Inventory::orderBy('name')->get(),
            'units' => \App\Models\Logistic\Unit::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to create material planning.');
        }

        $data = $request->validate([
            'plans' => 'required|array',
            'plans.*.project_id' => 'required|exists:projects,id',
            'plans.*.order_type' => 'required|in:material_req,purchase_req',
            'plans.*.material_name' => 'required|string',
            'plans.*.qty_needed' => 'required|numeric|min:0.01',
            'plans.*.unit_id' => 'required|exists:units,id',
            'plans.*.eta_date' => 'required|date',
        ]);

        $createdCount = 0;
        $materialReqCount = 0;
        $purchaseReqCount = 0;
        $errors = [];

        // Gunakan database transaction untuk memastikan data consistency
        DB::beginTransaction();

        try {
            // Array untuk menyimpan planning yang berhasil dibuat
            $createdPlannings = [];

            // Step 1: Buat semua material planning terlebih dahulu
            foreach ($data['plans'] as $planIndex => $plan) {
                try {
                    Log::info("Creating planning {$planIndex}: " . json_encode($plan));

                    $planning = MaterialPlanning::create([
                        'project_id' => $plan['project_id'],
                        'order_type' => $plan['order_type'],
                        'material_name' => $plan['material_name'],
                        'qty_needed' => $plan['qty_needed'],
                        'unit_id' => $plan['unit_id'],
                        'eta_date' => $plan['eta_date'],
                        'requested_by' => Auth::id(),
                    ]);

                    $createdPlannings[] = $planning;
                    $createdCount++;

                    Log::info('Planning created successfully with ID: ' . $planning->id);
                } catch (\Exception $e) {
                    Log::error("Error creating planning {$planIndex}: " . $e->getMessage());
                    $errors[] = "Error pada planning {$planIndex} - material: {$plan['material_name']} - " . $e->getMessage();
                }
            }

            // Step 2: Setelah semua planning berhasil, baru kirim ke modul lain
            foreach ($createdPlannings as $planning) {
                try {
                    if ($planning->order_type === 'material_req') {
                        Log::info('Processing material request for planning ID: ' . $planning->id);
                        $result = app(\App\Http\Controllers\MaterialRequestController::class)->storeFromPlanning($planning);
                        if ($result) {
                            $materialReqCount++;
                            Log::info('Material request created successfully for planning ID: ' . $planning->id);
                        } else {
                            Log::error('Failed to create material request for planning ID: ' . $planning->id);
                            $errors[] = "Gagal membuat Material Request untuk: {$planning->material_name}";
                        }
                    } else {
                        Log::info('Processing purchase request for planning ID: ' . $planning->id);
                        $result = app(\App\Http\Controllers\PurchaseRequestController::class)->storeFromPlanning($planning);
                        if ($result) {
                            $purchaseReqCount++;
                            Log::info('Purchase request created successfully for planning ID: ' . $planning->id);
                        } else {
                            Log::error('Failed to create purchase request for planning ID: ' . $planning->id);
                            $errors[] = "Gagal membuat Purchase Request untuk: {$planning->material_name}";
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error processing request for planning ID {$planning->id}: " . $e->getMessage());
                    $errors[] = "Error memproses {$planning->order_type} untuk material: {$planning->material_name} - " . $e->getMessage();
                }
            }

            // Jika tidak ada error fatal, commit transaction
            DB::commit();

            $successMessage = "Material planning berhasil! {$createdCount} planning dibuat ({$materialReqCount} Material Request, {$purchaseReqCount} Purchase Request)";

            if (count($errors) > 0) {
                return redirect()
                    ->route('material_planning.index')
                    ->with('success', $successMessage)
                    ->with('warning', 'Beberapa item gagal diproses: ' . implode('<br>', $errors));
            }

            return redirect()->route('material_planning.index')->with('success', $successMessage);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Fatal error in material planning store: ' . $e->getMessage());
            return redirect()
                ->route('material_planning.index')
                ->with('error', 'Terjadi kesalahan saat menyimpan planning: ' . $e->getMessage());
        }
    }

    // TAMBAHAN: Delete seluruh planning untuk project tertentu
    public function destroyProject($projectId)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'You do not have permission to delete material planning.',
                ],
                403,
            );
        }

        try {
            DB::beginTransaction();

            // Cek apakah project memiliki material planning
            $plannings = MaterialPlanning::where('project_id', $projectId)->get();

            if ($plannings->isEmpty()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'No material planning found for this project.',
                    ],
                    404,
                );
            }

            $projectName = $plannings->first()->project->name ?? 'Unknown Project';
            $materialCount = $plannings->count();

            // Hapus semua material planning untuk project ini
            MaterialPlanning::where('project_id', $projectId)->delete();

            DB::commit();

            Log::info("Deleted all material planning for project ID: {$projectId}");

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$materialCount} material planning items for project: {$projectName}",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting project material planning: ' . $e->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to delete material planning: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    // TAMBAHAN: Delete individual material planning
    public function destroy($id)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'You do not have permission to delete material planning.',
                ],
                403,
            );
        }

        try {
            $planning = MaterialPlanning::findOrFail($id);
            $materialName = $planning->material_name;
            $projectName = $planning->project->name ?? 'Unknown Project';

            $planning->delete();

            Log::info("Deleted material planning ID: {$id} - {$materialName}");

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted material planning: {$materialName} from project: {$projectName}",
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting material planning: ' . $e->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to delete material planning: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function getRelatedItems($projectId)
    {
        try {
            // Validasi project exists
            $project = Project::findOrFail($projectId);

            \Log::info("Fetching related items for project ID: {$projectId}");

            // Query Purchase Request yang match dengan project ini
            $relatedItems = PurchaseRequest::where('project_id', $projectId)
                ->whereIn('approval_status', ['pending', 'approved', 'Pending', 'Approved'])
                ->orderBy('created_at', 'desc')
                ->get();

            \Log::info('Found ' . $relatedItems->count() . ' related items');

            // Format data untuk response
            $items = $relatedItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'material_name' => $item->material_name,
                    'qty_needed' => $item->required_quantity,
                    'unit' => $item->unit ?? '-',
                    'eta_date' => $item->delivery_date ?? '-',
                    'supplier' => $item->supplier ?? '-',
                    'price_per_unit' => $item->price_per_unit ?? '-',
                    'approval_status' => ucfirst(strtolower($item->approval_status)),
                    'requested_by' => $item->requested_by ?? 'N/A',
                    'currency' => $item->currency_id ?? 'IDR',
                    'type' => $item->type ?? 'material_req',
                ];
            });

            \Log::info('Mapped items count: ' . $items->count());

            return response()->json([
                'success' => true,
                'count' => $items->count(),
                'items' => $items,
                'project_name' => $project->name,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getRelatedItems: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error loading related items: ' . $e->getMessage(),
                    'items' => [],
                    'count' => 0,
                ],
                500,
            );
        }
    }
}
