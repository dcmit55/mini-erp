<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Production\Project;
use App\Models\Logistic\MaterialUsage;
use App\Models\Logistic\GoodsIn;
use App\Models\Logistic\GoodsOut;
use App\Models\Admin\Department;
use App\Models\Lark\LarkBtSgItemTracking;
use App\Models\Lark\LarkSgBtItemTracking;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProjectCostingExport;
use App\Exports\AllProjectsCostingExport;
use App\Exports\ProjectCostingMultiSheetExport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Logistic\Inventory;

class ProjectCostingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $rolesAllowed = ['super_admin', 'admin_finance', 'admin'];
            if (!in_array(Auth::user()->role, $rolesAllowed)) {
                abort(403, 'Unauthorized');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        // Rule: tampilkan semua project dengan project_status = 'Delivered' (tanpa filter stage)
        $query = Project::where('project_status', 'Delivered');

        // Search filter
        if ($request->has('search') && $request->search !== null) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        // Apply filters
        if ($request->has('department') && $request->department !== null && $request->department !== '') {
            $query->where('type_dept', 'LIKE', '%' . $request->department . '%');
        }

        // Filter by sales (field may contain comma-separated names)
        if ($request->filled('sales')) {
            $query->where(function ($q) use ($request) {
                $name = $request->sales;
                // Exact full-field match OR name appears within a comma-separated list
                $q->where('sales', $name)
                    ->orWhere('sales', 'like', $name . ',%')
                    ->orWhere('sales', 'like', '%, ' . $name . ',%')
                    ->orWhere('sales', 'like', '%, ' . $name);
            });
        }

        // Filter by job order - show only projects that have this job order
        if ($request->has('job_order') && $request->job_order !== null) {
            $query->whereHas('jobOrders', function ($q) use ($request) {
                $q->where('id', $request->job_order);
            });
        }

        // Filter by deadline month
        if ($request->has('deadline_month') && $request->deadline_month !== null) {
            $query->whereRaw('DATE_FORMAT(deadline, "%Y-%m") = ?', [$request->deadline_month]);
        }

        // Filter by date range (deadline between date_from and date_to)
        if ($request->filled('date_from')) {
            $query->whereDate('deadline', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('deadline', '<=', $request->date_to);
        }

        $projects = $query
            ->with(['departments', 'jobOrders.materialRequests', 'jobOrders.department'])
            ->orderByDesc('deadline') // Sort by deadline descending
            ->orderByDesc('created_at') // Then by created_at
            ->paginate(10);

        // Card summaries: UI only — data queries disabled pending column audit
        $cardSummaries = [];

        // Get unique type_dept values from closed/delivered projects
        $rawDeptValues = Project::where('project_status', 'Delivered')->whereNotNull('type_dept')->where('type_dept', '!=', '')->pluck('type_dept');
        $departments = $rawDeptValues->flatMap(fn($v) => array_map('trim', explode(',', $v)))->filter()->unique()->sort()->values();

        // Get unique individual sales names (field may contain comma-separated values)
        $rawSales = Project::where('project_status', 'Delivered')->whereNotNull('sales')->pluck('sales');
        $salesOptions = $rawSales->flatMap(fn($s) => array_map('trim', explode(',', $s)))->filter()->unique()->sort()->values();

        // Get all job orders for filter dropdown
        $jobOrders = \App\Models\Production\JobOrder::select('id', 'name')->orderBy('name')->get();

        // Get unique months from deadline for filter dropdown
        $deadlineMonths = Project::where('project_status', 'Delivered')->whereNotNull('deadline')->selectRaw('DATE_FORMAT(deadline, "%Y-%m") as month')->distinct()->orderByDesc('month')->pluck('month');

        return view('finance.costing.index', compact('projects', 'departments', 'salesOptions', 'jobOrders', 'deadlineMonths', 'cardSummaries'));
    }

    /**
     * Full-page detail view for a project costing (server-side rendered)
     */
    public function showDetail($project_id)
    {
        $project = Project::where('id', $project_id)->where('project_status', 'Delivered')->firstOrFail();
        $project->load(['departments', 'jobOrders.department']);

        // ── Material Usages ──
        $usages = MaterialUsage::where('project_id', $project_id)
            ->with(['inventory.currency', 'inventory.unit', 'inventory.batches', 'jobOrder'])
            ->orderBy('job_order_id')
            ->get();

        $materialsData = $usages->map(function ($usage) {
            $inv = $usage->inventory;

            // Guard: inventory may be null (orphaned usage row or soft-deleted item)
            if (!$inv) {
                return null;
            }

            $unitPrice  = $inv->price ?? 0;
            $domFreight = $inv->unit_domestic_freight_cost ?? 0;
            $intFreight = $inv->unit_international_freight_cost ?? 0;
            $totalUnit  = $unitPrice + $domFreight + $intFreight;
            $qty        = $usage->used_quantity ?? 0;
            $currency   = $inv->currency ?? (object) ['name' => 'IDR', 'exchange_rate' => 1];
            $rate       = $currency->exchange_rate ?? 1;
            $totalIDR   = $totalUnit * $qty * $rate;

            // Unit name: prefer unit relation, fall back to varchar field
            $unitName = 'N/A';
            if ($inv->unit_id && $inv->unit) {
                $unitName = $inv->unit->name ?? 'N/A';
            } elseif (!empty($inv->unit) && is_string($inv->unit)) {
                $unitName = $inv->unit;
            }

            $currCode = strtoupper($currency->name ?? 'IDR');
            $isIntl   = in_array($currCode, ['RMB', 'CNY', 'SGD', 'USD', 'EUR', 'GBP']);

            return [
                'job_order_name' => $usage->jobOrder?->name ?? 'No Job Order',
                'name'           => $inv->name ?? 'N/A',
                'qty'            => $qty,
                'unit'           => $unitName,
                'unit_price'     => $unitPrice,
                'total_unit_cost'=> $totalUnit,
                'currency'       => $currCode,
                'total_idr'      => $totalIDR,
                'is_intl'        => $isIntl,
                'source'         => 'usage',
            ];
        })->filter()->values(); // drop null rows (orphaned usages)

        $intlMaterials    = $materialsData->where('is_intl', true)->values();
        $localMaterials   = $materialsData->where('is_intl', false)->values();
        $totalMaterialIDR = $materialsData->sum('total_idr');
        $usageCostIDR     = $materialsData->sum('total_idr');

        // ── DCM Costings (INT'L PO & LOCAL PO) ──
        $dcmCostings = \App\Models\Finance\DcmCosting::where('project_name', $project->name)->where('is_current', true)->get();

        $intlPoCostings = $dcmCostings->filter(fn($c) => str_contains(strtolower($c->purchase_type ?? ''), 'intl') || str_contains(strtolower($c->purchase_type ?? ''), 'international') || str_contains(strtolower($c->supplier ?? ''), 'sg') || str_contains(strtolower($c->department ?? ''), 'sg'));
        $localPoCostings = $dcmCostings->filter(fn($c) => !$intlPoCostings->contains('id', $c->id));

        $totalIntlPo = $intlPoCostings->sum('invoice_total');
        $totalLocalPo = $localPoCostings->sum('invoice_total');
        $totalPoIDR = $totalIntlPo + $totalLocalPo;

        // ── Labor (Timings) ──
        $timings = \App\Models\Production\Timing::where('project_id', $project_id)
            ->where('approval_status', 'approved')
            ->with(['jobOrder', 'employee'])
            ->orderBy('start_time')
            ->get();

        $totalLaborMinutes = $timings->sum('duration_minutes');
        $totalLaborHours = round($totalLaborMinutes / 60, 2);

        $timingsByJobOrder = $timings
            ->groupBy('job_order_id')
            ->map(function ($rows) {
                $jo = $rows->first()->jobOrder;
                return [
                    'job_order_name' => $jo?->name ?? 'No Job Order',
                    'total_hours' => round($rows->sum('duration_minutes') / 60, 2),
                    'sessions_count' => $rows->count(),
                    'rows' => $rows
                        ->map(
                            fn($t) => [
                                'employee' => $t->employee?->name ?? '—',
                                'role' => $t->employee?->position ?? '—',
                                'start_time' => $t->start_time ? \Carbon\Carbon::parse($t->start_time)->format('H:i') : '—',
                                'end_time' => $t->end_time ? \Carbon\Carbon::parse($t->end_time)->format('H:i') : '—',
                                'hours' => round(($t->duration_minutes ?? 0) / 60, 2),
                            ],
                        )
                        ->values()
                        ->toArray(),
                ];
            })
            ->values();

        // ── Courier (Freight) ──
        $courierData = $this->getCourierCosts($project_id);
        $totalFreightIDR = $courierData['total_idr'] ?? 0;

        // ── Grand total (Actual Project Cost) ──
        $grandTotal = $totalMaterialIDR + $totalFreightIDR;

        // ── Overhead = from stock usage portion ──
        $overheadIDR = $usageCostIDR; // material usage from existing stock

        return view('finance.costing.show', compact('project', 'intlMaterials', 'localMaterials', 'totalMaterialIDR', 'usageCostIDR', 'timingsByJobOrder', 'totalLaborHours', 'timings', 'courierData', 'totalFreightIDR', 'dcmCostings', 'totalIntlPo', 'totalLocalPo', 'totalPoIDR', 'grandTotal', 'overheadIDR'));
    }

    /**
     * Full Material Cost detail page
     */
    public function showMaterialDetail($project_id)
    {
        $project = Project::where('id', $project_id)->where('project_status', 'Delivered')->firstOrFail();
        $project->load(['departments', 'jobOrders']);

        $usages = MaterialUsage::where('project_id', $project_id)
            ->with(['inventory.currency', 'inventory.unit', 'inventory.batches', 'jobOrder'])
            ->orderBy('job_order_id')
            ->get();

        $materialsData = $usages->map(function ($usage) {
            $inv = $usage->inventory;

            // Guard: inventory may be null (orphaned usage row or soft-deleted item)
            if (!$inv) {
                return null;
            }

            $unitPrice  = $inv->price ?? 0;
            $domFreight = $inv->unit_domestic_freight_cost ?? 0;
            $intFreight = $inv->unit_international_freight_cost ?? 0;
            $totalUnit  = $unitPrice + $domFreight + $intFreight;
            $qty        = $usage->used_quantity ?? 0;
            $currency   = $inv->currency ?? (object) ['name' => 'IDR', 'exchange_rate' => 1];
            $rate       = $currency->exchange_rate ?? 1;
            $totalOrig  = $totalUnit * $qty;
            $totalIDR   = $totalUnit * $qty * $rate;

            // Unit name: prefer unit relation, fall back to varchar field
            $unitName = 'N/A';
            if ($inv->unit_id && $inv->unit) {
                $unitName = $inv->unit->name ?? 'N/A';
            } elseif (!empty($inv->unit) && is_string($inv->unit)) {
                $unitName = $inv->unit;
            }

            $currCode = strtoupper($currency->name ?? 'IDR');
            $isIntl   = in_array($currCode, ['RMB', 'CNY', 'SGD', 'USD', 'EUR', 'GBP']);

            return [
                'job_order_name'  => $usage->jobOrder?->name ?? 'No Job Order',
                'name'            => $inv->name ?? 'N/A',
                'qty'             => $qty,
                'unit'            => $unitName,
                'unit_price'      => $unitPrice,
                'total_unit_cost' => $totalUnit,
                'currency'        => $currCode,
                'exchange_rate'   => $rate,
                'total_original'  => $totalOrig,
                'total_idr'       => $totalIDR,
                'is_intl'         => $isIntl,
                'stock_location'  => $inv->location ?? ($isIntl ? 'Stock SG' : 'Stock BT'),
            ];
        })->filter()->values(); // drop null rows (orphaned usages)

        $intlMaterials = $materialsData->where('is_intl', true)->values();
        $localMaterials = $materialsData->where('is_intl', false)->values();
        $usageMaterials = $materialsData->values(); // all usage = Material Usage section
        $totalIntlIDR = $intlMaterials->sum('total_idr');
        $totalLocalIDR = $localMaterials->sum('total_idr');
        $totalMaterialIDR = $materialsData->sum('total_idr');

        // Exchange rates: pull from DB once, keyed by currency name
        $currencyNames = $intlMaterials->pluck('currency')->unique()->filter()->values()->toArray();
        $exchangeRates = \App\Models\Finance\Currency::whereIn('name', $currencyNames)->pluck('exchange_rate', 'name')->toArray();

        return view('finance.costing.material-detail', compact('project', 'intlMaterials', 'localMaterials', 'usageMaterials', 'totalIntlIDR', 'totalLocalIDR', 'totalMaterialIDR', 'exchangeRates'));
    }

    /**
     * Full Workmanship (Timing) detail page
     */
    public function showWorkmanshipDetail($project_id)
    {
        $project = Project::where('id', $project_id)->where('project_status', 'Delivered')->firstOrFail();
        $project->load(['departments', 'jobOrders']);

        $timings = \App\Models\Production\Timing::where('project_id', $project_id)
            ->where('status', 'complete')
            ->where('approval_status', 'approved')
            ->with(['jobOrder', 'employee.department'])
            ->orderBy('tanggal')
            ->orderBy('start_time')
            ->get();

        $totalLaborMinutes = $timings->sum('duration_minutes');
        $totalLaborHours = round($totalLaborMinutes / 60, 2);

        // Per-employee breakdown
        $byEmployee = $timings
            ->groupBy('employee_id')
            ->map(function ($rows) {
                $emp = $rows->first()->employee;
                $hrs = round($rows->sum('duration_minutes') / 60, 2);
                $salary = $emp->salary ?? 0;
                // Hourly rate: monthly salary / 173 (standard working hours)
                $hourlyRate = $salary > 0 ? round($salary / 173, 0) : 0;
                $laborCost = round($hourlyRate * $hrs, 0);
                return [
                    'name' => $emp?->name ?? '—',
                    'position' => $emp?->position ?? '—',
                    'initials' => strtoupper(substr($emp?->name ?? 'U', 0, 1)),
                    'hours' => $hrs,
                    'sessions' => $rows->count(),
                    'hourly_rate' => $hourlyRate,
                    'labor_cost' => $laborCost,
                ];
            })
            ->sortByDesc('hours')
            ->values();

        // Per date work sessions (for timeline)
        $workSessions = $timings
            ->groupBy(fn($t) => optional($t->tanggal)->format('d M Y') ?? '-')
            ->map(function ($rows, $date) {
                return [
                    'date' => $date,
                    'employees' => $rows->map(fn($t) => $t->employee?->name ?? '—')->unique()->values()->toArray(),
                    'hours' => round($rows->sum('duration_minutes') / 60, 2),
                    'sessions' => $rows->count(),
                ];
            })
            ->values();

        // Flat timing log rows
        $timingRows = $timings
            ->map(
                fn($t) => [
                    'employee' => $t->employee?->name ?? '—',
                    'initials' => strtoupper(substr($t->employee?->name ?? 'U', 0, 1)),
                    'position' => $t->employee?->position ?? '—',
                    'date' => optional($t->tanggal)->format('d M Y') ?? '—',
                    'start_time' => $t->start_time ? \Carbon\Carbon::parse($t->start_time)->format('H:i') : '—',
                    'end_time' => $t->end_time ? \Carbon\Carbon::parse($t->end_time)->format('H:i') : '—',
                    'hours' => round(($t->duration_minutes ?? 0) / 60, 2),
                    'job_order' => $t->jobOrder?->name ?? '—',
                ],
            )
            ->values();

        $totalOperators = $byEmployee->count();
        $avgHourlyRate = $byEmployee->avg('hourly_rate') ?? 0;
        $totalLaborCost = $byEmployee->sum('labor_cost');

        // Latest work date
        $latestDate = $timings->sortByDesc('tanggal')->first()?->tanggal;
        $latestDateFmt = $latestDate ? $latestDate->format('d M Y') : '—';

        return view('finance.costing.workmanship-detail', compact('project', 'timingRows', 'byEmployee', 'workSessions', 'totalLaborHours', 'totalOperators', 'avgHourlyRate', 'totalLaborCost', 'latestDateFmt'));
    }

    /**
     * Full Freight Cost detail page
     */
    public function showFreightDetail($project_id)
    {
        $project = Project::where('id', $project_id)->where('project_status', 'Delivered')->firstOrFail();
        $project->load(['departments', 'jobOrders']);

        // SG → BT items
        $sgBtItems = \App\Models\Lark\LarkSgBtItemTracking::with('courier')->where('project_id', $project_id)->get();

        // BT → SG items
        $btSgItems = \App\Models\Lark\LarkBtSgItemTracking::with('courier')->where('project_id', $project_id)->get();

        // Build SG → BT shipments grouped by courier
        $sgBtShipments = $sgBtItems
            ->whereNotNull('courier_id')
            ->groupBy('courier_id')
            ->map(function ($items) {
                $courier = $items->first()->courier;
                return [
                    'courier_name' => $courier->name ?? '—',
                    'carrier' => $courier->type_movement ?? '—',
                    'mode' => $courier->type_movement ?? '—',
                    'tracking' => $courier->lark_record_id ?? '—',
                    'date' => $courier->date ? $courier->date->format('d M Y') : '—',
                    'status' => 'Delivered',
                    'total_idr' => $courier->total_cost ?? 0,
                    'total_sgd' => $courier->total_cost_sgd ?? 0,
                    'items' => $items
                        ->map(
                            fn($i) => [
                                'name' => $i->item_name ?? '—',
                                'qty' => $i->qty ?? 1,
                                'status' => $i->status ?? 'Delivered',
                                'sgd_cost' => $i->sgd_cost ?? 0,
                            ],
                        )
                        ->values()
                        ->toArray(),
                    'items_count' => $items->count(),
                ];
            })
            ->values();

        // Build BT → SG shipments grouped by courier
        $btSgShipments = $btSgItems
            ->whereNotNull('courier_id')
            ->groupBy('courier_id')
            ->map(function ($items) {
                $courier = $items->first()->courier;
                return [
                    'courier_name' => $courier->name ?? '—',
                    'carrier' => $courier->type_movement ?? '—',
                    'mode' => $courier->type_movement ?? '—',
                    'tracking' => $courier->lark_record_id ?? '—',
                    'date' => $courier->date ? $courier->date->format('d M Y') : '—',
                    'status' => 'In Transit',
                    'total_idr' => $courier->total_cost ?? 0,
                    'total_sgd' => $courier->total_cost_sgd ?? 0,
                    'items' => $items
                        ->map(
                            fn($i) => [
                                'name' => $i->item_name ?? '—',
                                'qty' => $i->qty ?? 1,
                                'status' => $i->status ?? 'In Transit',
                                'sgd_cost' => $i->sgd_cost ?? 0,
                            ],
                        )
                        ->values()
                        ->toArray(),
                    'items_count' => $items->count(),
                ];
            })
            ->values();

        $totalSgBtIDR = $sgBtShipments->sum('total_idr');
        $totalBtSgIDR = $btSgShipments->sum('total_idr');
        $totalFreightIDR = $totalSgBtIDR + $totalBtSgIDR;
        $sgBtCount = $sgBtItems->count();
        $btSgCount = $btSgItems->count();

        return view('finance.costing.freight-detail', compact('project', 'sgBtShipments', 'btSgShipments', 'totalSgBtIDR', 'totalBtSgIDR', 'totalFreightIDR', 'sgBtCount', 'btSgCount'));
    }

    public function viewCosting($project_id)
    {
        // Hanya project dengan project_status=Delivered yang bisa di-view costing
        $project = Project::where('id', $project_id)->where('project_status', 'Delivered')->firstOrFail();

        // Ambil semua material usage untuk project dengan eager load jobOrder
        $usages = MaterialUsage::where('project_id', $project_id)
            ->with(['inventory.currency', 'jobOrder'])
            ->orderBy('job_order_id')
            ->get();

        // ===== LABOR COST FROM APPROVED TIMINGS =====
        $approvedTimings = \App\Models\Production\Timing::where('project_id', $project_id)
            ->where('approval_status', 'approved') // ❗ ONLY APPROVED
            ->with(['jobOrder', 'employee'])
            ->get();

        $totalLaborMinutes = $approvedTimings->sum('duration_minutes') ?? 0;
        $totalLaborHours = round($totalLaborMinutes / 60, 2);
        $approvedTimingsCount = $approvedTimings->count();

        // Group timings by job order for detailed breakdown
        $timingsByJobOrder = $approvedTimings
            ->groupBy('job_order_id')
            ->map(function ($timings, $jobOrderId) {
                $jobOrder = $timings->first()->jobOrder;
                $totalMinutes = $timings->sum('duration_minutes');
                $totalHours = round($totalMinutes / 60, 2);

                return [
                    'job_order_id' => $jobOrderId,
                    'job_order_name' => $jobOrder ? $jobOrder->name : 'No Job Order',
                    'total_hours' => $totalHours,
                    'total_minutes' => $totalMinutes,
                    'sessions_count' => $timings->count(), // Match frontend variable name
                    'unique_employees' => $timings->pluck('employee.name')->unique()->count(), // Count employees
                    'employee_names' => $timings->pluck('employee.name')->unique()->values()->toArray(), // List for tooltip
                ];
            })
            ->values();

        // Hitung total biaya per material dengan rumus baru dan konversi ke IDR
        $materials = $usages->map(function ($usage) {
            $inventory = $usage->inventory;

            // Guard: inventory may be null (orphaned usage row or soft-deleted item)
            if (!$inventory) {
                return null;
            }

            $unitPrice           = $inventory->price ?? 0;
            $domesticFreight     = $inventory->unit_domestic_freight_cost ?? 0;
            $internationalFreight = $inventory->unit_international_freight_cost ?? 0;
            $totalUnitCost       = $unitPrice + $domesticFreight + $internationalFreight;
            $usedQty             = $usage->used_quantity ?? 0;

            // Unit name: prefer unit relation (FK), fall back to varchar field
            $unitName = 'N/A';
            if ($inventory->unit_id && $inventory->unit) {
                $unitName = $inventory->unit->name ?? 'N/A';
            } elseif (!empty($inventory->unit) && is_string($inventory->unit)) {
                $unitName = $inventory->unit;
            }

            $currency     = $inventory->currency ?? (object) ['name' => 'IDR', 'exchange_rate' => 1];
            $exchangeRate = $currency->exchange_rate ?? 1;
            $totalCost    = $totalUnitCost * $usedQty;
            $totalCostInIDR = $totalCost * $exchangeRate;

            return (object) [
                'job_order_name' => $usage->jobOrder?->name ?? 'No Job Order',
                'inventory' => (object) [
                    'id'             => $inventory->id ?? $usage->inventory_id,
                    'name'           => $inventory->name ?? 'N/A',
                    'unit'           => $unitName,
                    'price'          => $unitPrice,
                    'total_unit_cost'=> $totalUnitCost,
                    'currency'       => $currency,
                ],
                'used_quantity' => $usedQty,
                'total_price'   => $totalCost,
                'total_cost'    => $totalCostInIDR,
            ];
        })->filter()->values(); // drop null rows (orphaned usages)

        // Hitung grand total dalam IDR
        $grand_total_material_idr = $materials->sum('total_cost');

        // ===== GET COURIER COSTS =====
        $courierCosts = $this->getCourierCosts($project_id);

        return response()->json([
            'project' => $project->name,
            'materials' => $materials,
            'grand_total_material_idr' => $grand_total_material_idr,
            // ===== LABOR COST DATA =====
            'labor' => [
                'total_hours' => $totalLaborHours,
                'total_minutes' => $totalLaborMinutes,
                'approved_sessions_count' => $approvedTimingsCount,
                'by_job_order' => $timingsByJobOrder,
            ],
            // ===== COURIER COSTS DATA =====
            'courier' => $courierCosts,
            // ===== COMBINED TOTALS =====
            'summary' => [
                'total_material_cost_idr' => $grand_total_material_idr,
                'total_labor_hours' => $totalLaborHours,
                'total_courier_cost_sgd' => $courierCosts['total_sgd'],
                'job_order_count' => $usages->pluck('job_order_id')->unique()->count(),
                'approved_timing_count' => $approvedTimingsCount,
            ],
        ]);
    }

    public function exportCosting($project_id)
    {
        // Hanya project dengan project_status=Delivered yang bisa di-export
        $project = Project::where('id', $project_id)->where('project_status', 'Delivered')->firstOrFail();

        // ── 1. Material Cost rows ─────────────────────────────────────────────
        $usages = MaterialUsage::where('project_id', $project_id)
            ->with(['inventory.currency', 'inventory.unit', 'jobOrder'])
            ->orderBy('job_order_id')
            ->get();

        $materialRows = $usages->map(function ($usage) {
            $inv = $usage->inventory;

            // Guard: inventory may be null (orphaned usage row or soft-deleted item)
            if (!$inv) {
                return null;
            }

            $unitPrice  = $inv->price ?? 0;
            $domFreight = $inv->unit_domestic_freight_cost ?? 0;
            $intFreight = $inv->unit_international_freight_cost ?? 0;
            $totalUnit  = $unitPrice + $domFreight + $intFreight;
            $qty        = $usage->used_quantity ?? 0;
            $currency   = $inv->currency ?? (object)['name' => 'IDR', 'exchange_rate' => 1];
            $rate       = $currency->exchange_rate ?? 1;
            $totalIdr   = $totalUnit * $qty * $rate;

            // Unit name: prefer unit relation, fall back to varchar field
            $unitName = 'N/A';
            if ($inv->unit_id && $inv->unit) {
                $unitName = $inv->unit->name ?? 'N/A';
            } elseif (!empty($inv->unit) && is_string($inv->unit)) {
                $unitName = $inv->unit;
            }

            return [
                'job_order_name'   => $usage->jobOrder?->name ?? 'No Job Order',
                'material_name'    => $inv->name ?? 'N/A',
                'qty'              => $qty,
                'unit'             => $unitName,
                'currency'         => strtoupper($currency->name ?? 'IDR'),
                'unit_price'       => $unitPrice,
                'domestic_freight' => $domFreight,
                'intl_freight'     => $intFreight,
                'total_unit_cost'  => $totalUnit,
                'total_idr'        => $totalIdr,
            ];
        })->filter()->values()->toArray(); // drop null rows (orphaned usages)

        // ── 2. Workmanship Cost rows ─────────────────────────────────────────
        $timings = \App\Models\Production\Timing::where('project_id', $project_id)
            ->where('status', 'complete')
            ->where('approval_status', 'approved')
            ->with(['jobOrder', 'employee'])
            ->orderBy('tanggal')
            ->orderBy('start_time')
            ->get();

        $workmanshipRows = $timings->map(function ($t) {
            $emp        = $t->employee;
            $hrs        = round(($t->duration_minutes ?? 0) / 60, 2);
            $salary     = $emp->salary ?? 0;
            $hourlyRate = $salary > 0 ? round($salary / 173, 0) : 0;
            $laborCost  = round($hourlyRate * $hrs, 0);

            return [
                'employee'    => $emp?->name ?? '—',
                'position'    => $emp?->position ?? '—',
                'date'        => optional($t->tanggal)->format('d M Y') ?? '—',
                'start_time'  => $t->start_time ? \Carbon\Carbon::parse($t->start_time)->format('H:i') : '—',
                'end_time'    => $t->end_time   ? \Carbon\Carbon::parse($t->end_time)->format('H:i')   : '—',
                'hours'       => $hrs,
                'job_order'   => $t->jobOrder?->name ?? '—',
                'step'        => $t->step ?? '—',
                'hourly_rate' => $hourlyRate,
                'labor_cost'  => $laborCost,
            ];
        })->toArray();

        // ── 3. Freight Cost rows ─────────────────────────────────────────────
        $courierData  = $this->getCourierCosts($project_id);
        $freightRows  = $courierData['couriers']->toArray();

        // ── 4. Summary row (single project) ─────────────────────────────────
        $dcmCostings   = \App\Models\Finance\DcmCosting::where('project_name', $project->name)->where('is_current', true)->get();
        $intlPoCostings = $dcmCostings->filter(fn($c) =>
            str_contains(strtolower($c->purchase_type ?? ''), 'intl') ||
            str_contains(strtolower($c->purchase_type ?? ''), 'international') ||
            str_contains(strtolower($c->supplier ?? ''), 'sg') ||
            str_contains(strtolower($c->department ?? ''), 'sg')
        );
        $localPoCostings = $dcmCostings->filter(fn($c) => !$intlPoCostings->contains('id', $c->id));
        $totalIntlPo   = $intlPoCostings->sum('invoice_total');
        $totalLocalPo  = $localPoCostings->sum('invoice_total');
        $usageIdr      = collect($materialRows)->sum('total_idr');

        $summaryRows = [[
            'project_name' => $project->name,
            'type_dept'    => $project->type_dept ?? '-',
            'sales'        => $project->sales ?? '-',
            'deadline'     => $project->deadline ? \Carbon\Carbon::parse($project->deadline)->format('d M Y') : '-',
            'intl_po'      => $totalIntlPo,
            'local_po'     => $totalLocalPo,
            'usage_idr'    => $usageIdr,
        ]];

        $filename = 'costing_' . \Illuminate\Support\Str::slug($project->name) . '_' . now()->format('Ymd') . '.xlsx';

        return Excel::download(
            new ProjectCostingMultiSheetExport(
                $project->name,
                $summaryRows,
                $materialRows,
                $workmanshipRows,
                $freightRows,
                ['project' => $project->name]
            ),
            $filename
        );
    }

    public function exportAllProjects(Request $request)
    {
        try {
            // Export hanya project yang project_status = Delivered
            $query = Project::where('project_status', 'Delivered');

            if ($request->has('department') && $request->department !== null) {
                $query->whereHas('departments', function ($q) use ($request) {
                    $q->where('name', $request->department);
                });
            }

            $projects = $query->with('departments')->orderBy('name')->get();
            $projectsData = [];

            foreach ($projects as $project) {
                $usages = MaterialUsage::where('project_id', $project->id)
                    ->with(['inventory.currency', 'inventory.unit'])
                    ->get();

                if ($usages->isEmpty()) {
                    continue;
                }

                $materials = [];
                $projectTotal = 0;

                foreach ($usages as $usage) {
                    $inventory = $usage->inventory;

                    if (!$inventory) {
                        continue;
                    }

                    $currency = $inventory->currency;
                    $unitPrice = $inventory->price ?? 0;
                    $domesticFreight = $inventory->unit_domestic_freight_cost ?? 0;
                    $internationalFreight = $inventory->unit_international_freight_cost ?? 0;
                    $totalUnitCost = $unitPrice + $domesticFreight + $internationalFreight;

                    $exchangeRate = $currency ? $currency->exchange_rate ?? 1 : 1;
                    $usedQty = $usage->used_quantity ?? 0;
                    $totalCostIdr = $totalUnitCost * $usedQty * $exchangeRate;

                    $projectTotal += $totalCostIdr;

                    // Get unit name - FIXED: Check relation loaded properly
                    $unitName = 'N/A';

                    // Check if unit relation is loaded (not just varchar field)
                    if ($inventory->relationLoaded('unit') && $inventory->unit && is_object($inventory->unit)) {
                        // Unit is loaded as relation object
                        $unitName = $inventory->unit->name ?? 'N/A';
                    } elseif (!empty($inventory->unit) && is_string($inventory->unit)) {
                        // Unit is varchar field (legacy data)
                        $unitName = $inventory->unit;
                    }

                    $materials[] = [
                        'material_name' => $inventory->name ?? 'N/A',
                        'qty' => $usedQty,
                        'unit' => $unitName,
                        'currency' => $currency ? $currency->name ?? 'IDR' : 'IDR',
                        'unit_price' => $unitPrice,
                        'domestic_freight' => $domesticFreight,
                        'intl_freight' => $internationalFreight,
                        'total_unit_cost' => $totalUnitCost,
                        'total_cost_idr' => $totalCostIdr,
                    ];
                }

                if (!empty($materials)) {
                    $projectsData[] = [
                        'project_name' => $project->name,
                        'materials' => $materials,
                        'grand_total' => $projectTotal,
                    ];
                }
            }

            if (empty($projectsData)) {
                return back()->with('error', 'No projects with material usage found.');
            }

            $filename = 'all_projects_costing_' . now()->format('Y-m-d_His') . '.xlsx';

            return Excel::download(new AllProjectsCostingExport($projectsData), $filename);
        } catch (\Exception $e) {
            Log::error('Export All Projects Error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Export failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Get materials breakdown by job order (AJAX endpoint)
     */
    public function getJobOrderMaterials($project_id, $job_order_id)
    {
        try {
            $project = Project::findOrFail($project_id);
            $jobOrder = \App\Models\Production\JobOrder::findOrFail($job_order_id);

            // Get material usages for this job order
            $usages = MaterialUsage::where('project_id', $project_id)
                ->where('job_order_id', $job_order_id)
                ->with(['inventory.currency', 'inventory.unit', 'jobOrder'])
                ->get();

            // Calculate costs per material
            $materials = $usages->map(function ($usage) {
                $inventory = $usage->inventory;

                // RUMUS: Unit Price + Domestic Freight + International Freight
                $unitPrice = $inventory->price ?? 0;
                $domesticFreight = $inventory->unit_domestic_freight_cost ?? 0;
                $internationalFreight = $inventory->unit_international_freight_cost ?? 0;
                $totalUnitCost = $unitPrice + $domesticFreight + $internationalFreight;

                $usedQty = $usage->used_quantity ?? 0;

                // Get unit name
                $unitName = 'N/A';
                if ($inventory->unit_id) {
                    try {
                        $unitRelation = $inventory->unit;
                        if ($unitRelation) {
                            $unitName = $unitRelation->name;
                        }
                    } catch (\Exception $e) {
                        $unitName = $inventory->unit ?? 'N/A';
                    }
                } elseif (!empty($inventory->unit)) {
                    $unitName = $inventory->unit;
                }

                $currency = $inventory->currency ?? (object) ['name' => 'N/A', 'exchange_rate' => 1];
                $exchangeRate = $currency->exchange_rate ?? 1;

                $totalCost = $totalUnitCost * $usedQty;
                $totalCostInIDR = $totalCost * $exchangeRate;

                return [
                    'job_order_name' => $usage->jobOrder ? $usage->jobOrder->name : 'No Job Order',
                    'material_name' => $inventory->name ?? 'N/A',
                    'quantity' => number_format($usedQty, 2),
                    'unit' => $unitName,
                    'unit_price' => number_format($unitPrice, 2),
                    'domestic_freight' => number_format($domesticFreight, 2),
                    'international_freight' => number_format($internationalFreight, 2),
                    'total_unit_cost' => number_format($totalUnitCost, 2),
                    'total_cost_idr' => 'Rp ' . number_format($totalCostInIDR, 2, ',', '.'),
                    'currency' => $currency->name ?? 'N/A',
                ];
            });

            return response()->json([
                'success' => true,
                'job_order' => [
                    'id' => $jobOrder->id,
                    'name' => $jobOrder->name,
                ],
                'materials' => $materials,
                'total_materials' => $materials->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Get courier costs for a specific project
     * Aggregates both BT-SG and SG-BT courier costs
     */
    private function getCourierCosts($projectId)
    {
        // Get BT-SG items with courier
        $btSgItems = LarkBtSgItemTracking::with('courier')->where('project_id', $projectId)->whereNotNull('courier_id')->get();

        // Get SG-BT items with courier
        $sgBtItems = LarkSgBtItemTracking::with('courier')->where('project_id', $projectId)->whereNotNull('courier_id')->get();

        // Group BT-SG by courier
        $btSgCouriers = $btSgItems->groupBy('courier_id')->map(function ($items, $courierId) {
            $courier = $items->first()->courier;
            return [
                'courier_id' => $courierId,
                'courier_name' => $courier->name ?? 'Unknown',
                'direction' => 'BT → SG',
                'date' => $courier->date ? $courier->date->format('d M Y') : '-',
                'items_count' => $items->count(),
                'items' => $items->pluck('item_name')->toArray(),
                'transport_cost' => $courier->transport_cost ?? 0,
                'baggage_cost' => $courier->baggage_cost ?? 0,
                'gst_cost' => $courier->gst_cost ?? 0,
                'total_idr' => $courier->total_cost ?? 0,
                'total_sgd' => $courier->total_cost_sgd ?? 0,
            ];
        });

        // Group SG-BT by courier
        $sgBtCouriers = $sgBtItems->groupBy('courier_id')->map(function ($items, $courierId) {
            $courier = $items->first()->courier;
            return [
                'courier_id' => $courierId,
                'courier_name' => $courier->name ?? 'Unknown',
                'direction' => 'SG → BT',
                'date' => $courier->date ? $courier->date->format('d M Y') : '-',
                'items_count' => $items->count(),
                'items' => $items->pluck('item_name')->toArray(),
                'transport_cost' => $courier->transport_cost ?? 0,
                'baggage_cost' => $courier->baggage_cost ?? 0,
                'gst_cost' => $courier->gst_cost ?? 0,
                'total_idr' => $courier->total_cost ?? 0,
                'total_sgd' => $courier->total_cost_sgd ?? 0,
            ];
        });

        // Combine both directions
        $allCouriers = $btSgCouriers->merge($sgBtCouriers)->values();

        // Calculate totals
        $totalSgd = $allCouriers->sum('total_sgd');
        $totalIdr = $allCouriers->sum('total_idr');

        return [
            'bt_sg_count' => $btSgCouriers->count(),
            'sg_bt_count' => $sgBtCouriers->count(),
            'total_couriers' => $allCouriers->count(),
            'total_items' => $btSgItems->count() + $sgBtItems->count(),
            'total_sgd' => round($totalSgd, 2),
            'total_idr' => round($totalIdr, 0),
            'couriers' => $allCouriers,
        ];
    }
}

// If you want to log $materials, place this inside a controller method where $materials is defined, for example:
// Log::info('Material Data:', $materials->toArray());
