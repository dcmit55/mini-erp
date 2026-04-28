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
use App\Models\Hr\OvertimeRequest;

class ProjectCostingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:finance.costing.view');
    }

    public function index(Request $request)
    {
        // Rule: tampilkan project Delivered + WIP (WIP ditandai secara visual di view)
        // Default = both. Filter by project_status when explicitly requested.
        $statusFilter = $request->input('project_status', 'all');
        $query = Project::where(function ($q) use ($statusFilter) {
            if ($statusFilter === 'delivered') {
                $q->where('project_status', 'Delivered');
            } elseif ($statusFilter === 'wip') {
                $q->where('project_status', 'LIKE', '%WIP%');
            } else {
                // all: Delivered OR WIP
                $q->where('project_status', 'Delivered')->orWhere('project_status', 'LIKE', '%WIP%');
            }
        });

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
            ->with(['departments', 'jobOrders' => fn($q) => $q->select('id', 'project_id', 'name', 'department_id', 'final_image'), 'jobOrders.department'])
            ->orderByDesc('deadline') // Sort by deadline descending
            ->orderByDesc('created_at') // Then by created_at
            ->paginate(12); // 12 = 3 cols × 4 rows (xl), fits clean grid

        // Card summaries: calculate actual_project_cost and total_project_time per project
        $projectIds = $projects->pluck('id')->toArray();
        $cardSummaries = [];

        if (!empty($projectIds)) {
            // Material cost: sum of (unit_price + freights) * qty * exchange_rate per project
            // NOTE: inventory.batches must be eager-loaded to avoid N+1 in getPriceAttribute()
            $usagesByProject = \App\Models\Logistic\MaterialUsage::whereIn('project_id', $projectIds)
                ->with(['inventory.currency', 'inventory.batches'])
                ->get()
                ->groupBy('project_id');

            // Timing (workmanship): sum duration_minutes per project + salary for cost
            $timingsByProject = \App\Models\Production\Timing::whereIn('project_id', $projectIds)->where('approval_status', 'approved')->selectRaw('project_id, SUM(duration_minutes) as total_minutes')->groupBy('project_id')->pluck('total_minutes', 'project_id');

            // Workmanship cost: need snapshotted rate_per_hour (or fallback employee salary)
            // Select rate_per_hour along with employee salary as fallback
            $timingsWithEmployee = \App\Models\Production\Timing::whereIn('project_id', $projectIds)->where('approval_status', 'approved')->with('employee:id,salary')->get()->groupBy('project_id');

            // DCM Costings (PO): sum invoice_total per project_name
            $projectNames = \App\Models\Production\Project::whereIn('id', $projectIds)->pluck('name', 'id');
            $dcmByProjectName = \App\Models\Finance\DcmCosting::whereIn('project_name', $projectNames->values())->where('is_current', true)->get()->groupBy('project_name');

            // Pre-fetch all courier tracking rows for all project IDs at once (avoids N+1 per project)
            $sgdRate = \App\Models\Finance\Currency::where('name', 'SGD')->value('exchange_rate') ?? 12000;
            $allBtSgItems = \App\Models\Lark\LarkBtSgItemTracking::whereIn('project_id', $projectIds)->get()->groupBy('project_id');
            $allSgBtItems = \App\Models\Lark\LarkSgBtItemTracking::whereIn('project_id', $projectIds)->get()->groupBy('project_id');

            foreach ($projectIds as $pid) {
                // Material cost
                $materialIDR = 0;
                foreach ($usagesByProject[$pid] ?? collect() as $usage) {
                    $inv = $usage->inventory;
                    if (!$inv) {
                        continue;
                    }
                    $rate = $inv->currency->exchange_rate ?? 1;
                    $unitCost = ($inv->price ?? 0) + ($inv->unit_domestic_freight_cost ?? 0) + ($inv->unit_international_freight_cost ?? 0);
                    $materialIDR += $unitCost * ($usage->used_quantity ?? 0) * $rate;
                }

                // Freight cost — sum sgd_cost directly from Lark tracking rows, convert to IDR
                $btSgForProject = $allBtSgItems[$pid] ?? collect();
                $sgBtForProject = $allSgBtItems[$pid] ?? collect();
                $freightIDR = round(($btSgForProject->sum('sgd_cost') + $sgBtForProject->sum('sgd_cost')) * $sgdRate, 0);

                // Workmanship cost: use snapshotted rate_per_hour if available,
                // otherwise fall back to employee's current salary (legacy data).
                $workmanshipIDR = 0;
                $totalMinutes = $timingsByProject[$pid] ?? 0;
                $totalHours = round($totalMinutes / 60, 2);
                foreach ($timingsWithEmployee[$pid] ?? collect() as $timing) {
                    // Prefer locked snapshot; fall back to live salary for old records
                    if (!is_null($timing->rate_per_hour) && (float) $timing->rate_per_hour > 0) {
                        $hourlyRate = (float) $timing->rate_per_hour;
                    } else {
                        $salary = $timing->employee->salary ?? 0;
                        $hourlyRate = $salary > 0 ? round($salary / 173, 0) : 0;
                    }
                    $hrs = round(($timing->duration_minutes ?? 0) / 60, 2);
                    $workmanshipIDR += round($hourlyRate * $hrs, 0);
                }

                // Actual Project Cost = material + workmanship + freight
                $actualCost = $materialIDR + $workmanshipIDR + $freightIDR;

                // INT'L PO & LOCAL PO from DCM costings
                $pName = $projectNames[$pid] ?? null;
                $dcmRows = $pName ? $dcmByProjectName[$pName] ?? collect() : collect();
                $intlRows = $dcmRows->filter(fn($c) => str_contains(strtolower($c->purchase_type ?? ''), 'intl') || str_contains(strtolower($c->purchase_type ?? ''), 'international') || str_contains(strtolower($c->supplier ?? ''), 'sg') || str_contains(strtolower($c->department ?? ''), 'sg'));
                $localRows = $dcmRows->filter(fn($c) => !$intlRows->contains('id', $c->id));

                $cardSummaries[$pid] = [
                    'actual_project_cost' => $actualCost,
                    'material_cost' => $materialIDR,
                    'workmanship_cost' => $workmanshipIDR,
                    'freight_cost' => $freightIDR,
                    'total_hours' => $totalHours,
                    'intl_po' => $intlRows->sum('invoice_total'),
                    'local_po' => $localRows->sum('invoice_total'),
                    'usage_idr' => $materialIDR,
                ];
            }
        }

        // Get unique type_dept values from delivered/WIP projects
        $rawDeptValues = Project::where(function ($q) {
            $q->where('project_status', 'Delivered')->orWhere('project_status', 'LIKE', '%WIP%');
        })
            ->whereNotNull('type_dept')
            ->where('type_dept', '!=', '')
            ->pluck('type_dept');
        $departments = $rawDeptValues->flatMap(fn($v) => array_map('trim', explode(',', $v)))->filter()->unique()->sort()->values();

        // Get unique individual sales names (field may contain comma-separated values)
        $rawSales = Project::where(function ($q) {
            $q->where('project_status', 'Delivered')->orWhere('project_status', 'LIKE', '%WIP%');
        })
            ->whereNotNull('sales')
            ->pluck('sales');
        $salesOptions = $rawSales->flatMap(fn($s) => array_map('trim', explode(',', $s)))->filter()->unique()->sort()->values();

        // Get all job orders for filter dropdown
        $jobOrders = \App\Models\Production\JobOrder::select('id', 'name')->orderBy('name')->get();

        // Get unique months from deadline for filter dropdown
        $deadlineMonths = Project::where(function ($q) {
            $q->where('project_status', 'Delivered')->orWhere('project_status', 'LIKE', '%WIP%');
        })
            ->whereNotNull('deadline')
            ->selectRaw('DATE_FORMAT(deadline, "%Y-%m") as month')
            ->distinct()
            ->orderByDesc('month')
            ->pluck('month');

        return view('finance.costing.index', compact('projects', 'departments', 'salesOptions', 'jobOrders', 'deadlineMonths', 'cardSummaries'));
    }

    /**
     * Full-page detail view for a project costing (server-side rendered)
     */
    public function showDetail($project_id)
    {
        $project = Project::where('id', $project_id)
            ->where(function ($q) {
                $q->where('project_status', 'Delivered')->orWhere('project_status', 'LIKE', '%WIP%');
            })
            ->firstOrFail();
        $project->load(['departments', 'jobOrders' => fn($q) => $q->select('id', 'project_id', 'name', 'department_id', 'final_image'), 'jobOrders.department']);
        // ── Material Usages ──
        $usages = MaterialUsage::where('project_id', $project_id)
            ->with(['inventory.currency', 'inventory.unitRelation', 'inventory.batches', 'jobOrder'])
            ->orderBy('job_order_id')
            ->get();

        $materialsData = $usages
            ->map(function ($usage) {
                $inv = $usage->inventory;

                // Guard: inventory may be null (orphaned usage row or soft-deleted item)
                if (!$inv) {
                    return null;
                }

                $unitPrice = $inv->price ?? 0;
                $domFreight = $inv->unit_domestic_freight_cost ?? 0;
                $intFreight = $inv->unit_international_freight_cost ?? 0;
                $totalUnit = $unitPrice + $domFreight + $intFreight;
                $qty = $usage->used_quantity ?? 0;
                $currency = $inv->currency ?? (object) ['name' => 'IDR', 'exchange_rate' => 1];
                $rate = $currency->exchange_rate ?? 1;
                $totalIDR = $totalUnit * $qty * $rate;

                // Unit name via single accessor (FK relation → fallback varchar)
                $unitName = $inv->unit_name ?: 'N/A';

                $currCode = strtoupper($currency->name ?? 'IDR');
                $isIntl = in_array($currCode, ['RMB', 'CNY', 'SGD', 'USD', 'EUR', 'GBP']);

                return [
                    'job_order_name' => $usage->jobOrder?->name ?? 'No Job Order',
                    'name' => $inv->name ?? 'N/A',
                    'qty' => $qty,
                    'unit' => $unitName,
                    'unit_price' => $unitPrice,
                    'total_unit_cost' => $totalUnit,
                    'currency' => $currCode,
                    'total_idr' => $totalIDR,
                    'is_intl' => $isIntl,
                    'source' => 'usage',
                ];
            })
            ->filter()
            ->values(); // drop null rows (orphaned usages)

        $intlMaterials = $materialsData->where('is_intl', true)->values();
        $localMaterials = $materialsData->where('is_intl', false)->values();
        $totalMaterialIDR = $materialsData->sum('total_idr');
        $usageCostIDR = $materialsData->sum('total_idr');

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

        // ── OT Requests (for OT-aware cost calculation) ──
        $projectJobOrdIds = $project->jobOrders->pluck('id')->toArray();
        $minDate = $timings->min(fn($t) => optional($t->tanggal)->format('Y-m-d'));
        $maxDate = $timings->max(fn($t) => optional($t->tanggal)->format('Y-m-d'));
        $otRequestsShow = \App\Models\Hr\OvertimeRequest::where('status', 'approved')
            ->when($minDate, fn($q) => $q->whereDate('start_time', '>=', $minDate))
            ->when($maxDate, fn($q) => $q->whereDate('start_time', '<=', $maxDate))
            ->where(function ($q) use ($projectJobOrdIds) {
                $q->whereNull('job_order_id');
                if (!empty($projectJobOrdIds)) {
                    $q->orWhereIn('job_order_id', $projectJobOrdIds);
                }
            })
            ->with('payDetail')
            ->get()
            ->groupBy(fn($ot) => $ot->employee_id . '_' . $ot->start_time->toDateString());

        // ── Workmanship Cost (IDR) — OT-aware ──
        $totalWorkmanshipIDR = 0;
        $totalNormalWorkmanship = 0;
        $totalOtWorkmanship = 0;
        $timingsByJobOrder = $timings
            ->groupBy('job_order_id')
            ->map(function ($rows) use (&$totalWorkmanshipIDR, &$totalNormalWorkmanship, &$totalOtWorkmanship, $otRequestsShow) {
                $jo = $rows->first()->jobOrder;
                $groupCost = 0;
                $rowData = $rows
                    ->map(function ($t) use (&$groupCost, &$totalNormalWorkmanship, &$totalOtWorkmanship, $otRequestsShow) {
                        if (!is_null($t->rate_per_hour) && (float) $t->rate_per_hour > 0) {
                            $hourlyRate = (int) round((float) $t->rate_per_hour);
                        } else {
                            $salary = $t->employee->salary ?? 0;
                            $hourlyRate = $salary > 0 ? round($salary / 173, 0) : 0;
                        }

                        $date = optional($t->tanggal)->format('Y-m-d') ?? '';
                        $key = $t->employee_id . '_' . $date;
                        $netMins = max(0, ($t->duration_minutes ?? 0) - ($t->break_deducted_minutes ?? 0));
                        $normalMins = $netMins;
                        $otMins = 0;
                        $otType = null;

                        $otReq = $otRequestsShow->get($key)?->first();
                        if ($otReq && $t->start_time && $t->end_time && $date) {
                            $timingStart = \Carbon\Carbon::parse($date . ' ' . $t->start_time);
                            $timingEnd = \Carbon\Carbon::parse($date . ' ' . $t->end_time);
                            $overlapStart = $timingStart->max($otReq->start_time);
                            $overlapEnd = $timingEnd->min($otReq->end_time);
                            if ($overlapEnd->gt($overlapStart)) {
                                $otMins = min($overlapEnd->diffInMinutes($overlapStart), $netMins);
                                $normalMins = max(0, $netMins - $otMins);
                                $otCode = $otReq->ot_code;
                                $otType = $otCode === 'Normal Day' ? 'weekday' : 'weekend';
                            }
                        }

                        $normalHrs = round($normalMins / 60, 2);
                        $otHrs = round($otMins / 60, 2);
                        $normalCost = round($hourlyRate * $normalHrs, 0);
                        $otCost = 0;

                        if ($otMins > 0 && $hourlyRate > 0) {
                            if ($otType === 'weekday') {
                                $h1_5 = min($otHrs, 1.0);
                                $h2 = max(0.0, $otHrs - 1.0);
                                $otCost = round($h1_5 * $hourlyRate * 1.5, 0) + round($h2 * $hourlyRate * 2.0, 0);
                            } else {
                                $h2 = min($otHrs, 7.0);
                                $h3 = max(0.0, min($otHrs - 7.0, 1.0));
                                $h4 = max(0.0, $otHrs - 8.0);
                                $otCost = round($h2 * $hourlyRate * 2.0, 0) + round($h3 * $hourlyRate * 3.0, 0) + round($h4 * $hourlyRate * 4.0, 0);
                            }
                        }

                        $totalCost = $normalCost + $otCost;
                        $groupCost += $totalCost;
                        $totalNormalWorkmanship += $normalCost;
                        $totalOtWorkmanship += $otCost;

                        return [
                            'employee' => $t->employee?->name ?? '—',
                            'role' => $t->employee?->position ?? '—',
                            'start_time' => $t->start_time ? \Carbon\Carbon::parse($t->start_time)->format('H:i') : '—',
                            'end_time' => $t->end_time ? \Carbon\Carbon::parse($t->end_time)->format('H:i') : '—',
                            'hours' => round($netMins / 60, 2),
                            'hourly_rate' => $hourlyRate,
                            'cost' => $totalCost,
                            'has_ot' => $otMins > 0,
                        ];
                    })
                    ->values()
                    ->toArray();
                $totalWorkmanshipIDR += $groupCost;
                return [
                    'job_order_name' => $jo?->name ?? 'No Job Order',
                    'total_hours' => round($rows->sum('duration_minutes') / 60, 2),
                    'sessions_count' => $rows->count(),
                    'total_cost' => $groupCost,
                    'rows' => $rowData,
                ];
            })
            ->values();

        // ── Courier (Freight) ──
        $courierData = $this->getCourierCosts($project_id);
        $totalFreightIDR = $courierData['total_idr'] ?? 0;

        // ── Grand total (Actual Project Cost = Material + Workmanship + Freight) ──
        // material_cost  = sum of inventory usage × unit cost × exchange rate
        // workmanship    = sum of (employee.salary / 173) × hours per timing session
        // freight_cost   = sum of courier costs from Lark BT-SG/SG-BT tracking
        $grandTotal = $totalMaterialIDR + $totalWorkmanshipIDR + $totalFreightIDR;

        // ── Overhead = from stock usage portion ──
        $overheadIDR = $usageCostIDR; // material usage from existing stock

        return view('finance.costing.show', compact('project', 'intlMaterials', 'localMaterials', 'totalMaterialIDR', 'usageCostIDR', 'timingsByJobOrder', 'totalLaborHours', 'timings', 'totalWorkmanshipIDR', 'totalNormalWorkmanship', 'totalOtWorkmanship', 'courierData', 'totalFreightIDR', 'dcmCostings', 'totalIntlPo', 'totalLocalPo', 'totalPoIDR', 'grandTotal', 'overheadIDR'));
    }

    /**
     * Full Material Cost detail page
     */
    public function showMaterialDetail($project_id)
    {
        $project = Project::where('id', $project_id)
            ->where(function ($q) {
                $q->where('project_status', 'Delivered')->orWhere('project_status', 'LIKE', '%WIP%');
            })
            ->firstOrFail();
        $project->load(['departments', 'jobOrders']);

        $usages = MaterialUsage::where('project_id', $project_id)
            ->with(['inventory.currency', 'inventory.unitRelation', 'inventory.batches', 'jobOrder'])
            ->orderBy('job_order_id')
            ->get();

        $materialsData = $usages
            ->map(function ($usage) {
                $inv = $usage->inventory;

                // Guard: inventory may be null (orphaned usage row or soft-deleted item)
                if (!$inv) {
                    return null;
                }

                $unitPrice = $inv->price ?? 0;
                $domFreight = $inv->unit_domestic_freight_cost ?? 0;
                $intFreight = $inv->unit_international_freight_cost ?? 0;
                $totalUnit = $unitPrice + $domFreight + $intFreight;
                $qty = $usage->used_quantity ?? 0;
                $currency = $inv->currency ?? (object) ['name' => 'IDR', 'exchange_rate' => 1];
                $rate = $currency->exchange_rate ?? 1;
                $totalOrig = $totalUnit * $qty;
                $totalIDR = $totalUnit * $qty * $rate;

                // Unit name via single accessor (FK relation → fallback varchar)
                $unitName = $inv->unit_name ?: 'N/A';

                $currCode = strtoupper($currency->name ?? 'IDR');
                $isIntl = in_array($currCode, ['RMB', 'CNY', 'SGD', 'USD', 'EUR', 'GBP']);

                return [
                    'job_order_name' => $usage->jobOrder?->name ?? 'No Job Order',
                    'name' => $inv->name ?? 'N/A',
                    'qty' => $qty,
                    'unit' => $unitName,
                    'unit_price' => $unitPrice,
                    'total_unit_cost' => $totalUnit,
                    'currency' => $currCode,
                    'exchange_rate' => $rate,
                    'total_original' => $totalOrig,
                    'total_idr' => $totalIDR,
                    'is_intl' => $isIntl,
                    'stock_location' => $inv->location ?? ($isIntl ? 'Stock SG' : 'Stock BT'),
                ];
            })
            ->filter()
            ->values(); // drop null rows (orphaned usages)

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
     * Full Workmanship (Timing) detail page — with OT cost integration.
     *
     * For each timing session, we check whether the employee has an approved/submitted
     * OT request on the same date. If the session overlaps with the OT window, the
     * overlapping portion is costed at the effective OT rate; the rest at normal rate.
     */
    public function showWorkmanshipDetail($project_id)
    {
        $project = Project::where('id', $project_id)
            ->where(function ($q) {
                $q->where('project_status', 'Delivered')->orWhere('project_status', 'LIKE', '%WIP%');
            })
            ->firstOrFail();
        $project->load(['departments', 'jobOrders']);

        $timings = \App\Models\Production\Timing::where('project_id', $project_id)
            ->where('status', 'complete')
            ->where('approval_status', 'approved')
            ->with(['jobOrder', 'employee.department'])
            ->orderBy('tanggal')
            ->orderBy('start_time')
            ->get();

        // ── Load approved OT requests — scoped to this project's employees, dates, and job orders ──
        $employeeIds = $timings->pluck('employee_id')->unique()->values()->toArray();
        $projectJobOrdIds = $timings->pluck('job_order_id')->filter()->unique()->values()->toArray();
        $timingDates = $timings->pluck('tanggal')->filter()->map(fn($d) => $d->format('Y-m-d'));
        $minDate = $timingDates->min();
        $maxDate = $timingDates->max();

        $otRequests = OvertimeRequest::whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            // Only OT dates that overlap with this project's work period
            ->when($minDate, fn($q) => $q->whereDate('start_time', '>=', $minDate))
            ->when($maxDate, fn($q) => $q->whereDate('start_time', '<=', $maxDate))
            // Only OT linked to this project's job orders (or OT with no job order set)
            ->where(function ($q) use ($projectJobOrdIds) {
                $q->whereNull('job_order_id');
                if (!empty($projectJobOrdIds)) {
                    $q->orWhereIn('job_order_id', $projectJobOrdIds);
                }
            })
            ->with('payDetail')
            ->get()
            ->groupBy(fn($ot) => $ot->employee_id . '_' . $ot->start_time->toDateString());

        // ── Helper: compute cost with per-multiplier OT breakdown ─────────────
        $computeCost = function (\App\Models\Production\Timing $t, int $hourlyRate) use ($otRequests): array {
            $date = optional($t->tanggal)->format('Y-m-d') ?? '';
            $key = $t->employee_id . '_' . $date;
            $netMins = max(0, ($t->duration_minutes ?? 0) - ($t->break_deducted_minutes ?? 0));

            $normalMins = $netMins;
            $otMins = 0;
            $otType = null; // 'weekday' | 'weekend'
            $otCode = null;
            $otStartFmt = null;
            $otEndFmt = null;

            $otReq = $otRequests->get($key)?->first();
            if ($otReq && $t->start_time && $t->end_time && $date) {
                $timingStart = \Carbon\Carbon::parse($date . ' ' . $t->start_time);
                $timingEnd = \Carbon\Carbon::parse($date . ' ' . $t->end_time);
                $overlapStart = $timingStart->max($otReq->start_time);
                $overlapEnd = $timingEnd->min($otReq->end_time);

                if ($overlapEnd->gt($overlapStart)) {
                    $otMins = min($overlapEnd->diffInMinutes($overlapStart), $netMins);
                    $normalMins = max(0, $netMins - $otMins);
                    $otCode = $otReq->ot_code;
                    $otType = $otCode === 'Normal Day' ? 'weekday' : 'weekend';
                    $otStartFmt = $overlapStart->format('H:i');
                    $otEndFmt = $overlapEnd->format('H:i');
                }
            }

            $normalHrs = round($normalMins / 60, 2);
            $otHrs = round($otMins / 60, 2);
            $normalCost = round($hourlyRate * $normalHrs, 0);

            // Per-multiplier breakdown (UU Ketenagakerjaan)
            $hrs1_5x = $cost1_5x = 0; // weekday only
            $hrs2x = $cost2x = 0; // weekday remainder or weekend first 7h
            $hrs3x = $cost3x = 0; // weekend jam ke-8
            $hrs4x = $cost4x = 0; // weekend jam ke-9+
            $otCost = 0;

            if ($otMins > 0 && $hourlyRate > 0) {
                if ($otType === 'weekday') {
                    // Jam pertama = 1.5×, selebihnya = 2×
                    $hrs1_5x = min($otHrs, 1.0);
                    $hrs2x = max(0.0, $otHrs - 1.0);
                    $cost1_5x = round($hrs1_5x * $hourlyRate * 1.5, 0);
                    $cost2x = round($hrs2x * $hourlyRate * 2.0, 0);
                } else {
                    // 7 jam pertama = 2×, jam ke-8 = 3×, jam ke-9+ = 4×
                    $hrs2x = min($otHrs, 7.0);
                    $hrs3x = max(0.0, min($otHrs - 7.0, 1.0));
                    $hrs4x = max(0.0, $otHrs - 8.0);
                    $cost2x = round($hrs2x * $hourlyRate * 2.0, 0);
                    $cost3x = round($hrs3x * $hourlyRate * 3.0, 0);
                    $cost4x = round($hrs4x * $hourlyRate * 4.0, 0);
                }
                $otCost = $cost1_5x + $cost2x + $cost3x + $cost4x;
            }

            // Multiplier label for display (e.g. "1.5×2×", "2×", "2×3×")
            $multLabel = null;
            if ($otType === 'weekday') {
                $multLabel = $hrs2x > 0 ? '1.5×2×' : '1.5×';
            } elseif ($otType === 'weekend') {
                if ($hrs4x > 0) {
                    $multLabel = '2×3×4×';
                } elseif ($hrs3x > 0) {
                    $multLabel = '2×3×';
                } else {
                    $multLabel = '2×';
                }
            }

            return [
                'normal_mins' => $normalMins,
                'ot_mins' => $otMins,
                'normal_hours' => $normalHrs,
                'ot_hours' => $otHrs,
                'ot_type' => $otType,
                'ot_code' => $otCode,
                'ot_start' => $otStartFmt,
                'ot_end' => $otEndFmt,
                'mult_label' => $multLabel,
                'normal_cost' => $normalCost,
                'ot_cost' => $otCost,
                'total_cost' => $normalCost + $otCost,
                'has_ot' => $otMins > 0,
                // Weekday breakdown
                'hrs_1_5x' => $hrs1_5x,
                'cost_1_5x' => $cost1_5x,
                'hrs_2x_wd' => $hrs2x,
                'cost_2x_wd' => $cost2x,
                // Weekend breakdown
                'hrs_2x_we' => $otType === 'weekend' ? $hrs2x : 0,
                'cost_2x_we' => $otType === 'weekend' ? $cost2x : 0,
                'hrs_3x' => $hrs3x,
                'cost_3x' => $cost3x,
                'hrs_4x' => $hrs4x,
                'cost_4x' => $cost4x,
            ];
        };

        // ── Per-employee breakdown ────────────────────────────────────────────
        $byEmployee = $timings
            ->groupBy('employee_id')
            ->map(function ($rows) use ($computeCost) {
                $emp = $rows->first()->employee;
                $salary = $emp->salary ?? 0;
                $liveFallbackRate = $salary > 0 ? round($salary / 173, 0) : 0;
                $snapshotRate = $rows->firstWhere(fn($t) => !is_null($t->rate_per_hour))?->rate_per_hour;
                $hourlyRate = $snapshotRate > 0 ? (int) round($snapshotRate) : $liveFallbackRate;

                $totals = ['normal_mins' => 0, 'ot_mins' => 0, 'normal_cost' => 0, 'ot_cost' => 0, 'wd_hrs' => 0.0, 'wd_cost' => 0.0, 'we_hrs' => 0.0, 'we_cost' => 0.0, 'hrs_1_5x' => 0.0, 'cost_1_5x' => 0, 'hrs_2x_wd' => 0.0, 'cost_2x_wd' => 0, 'hrs_2x_we' => 0.0, 'cost_2x_we' => 0, 'hrs_3x' => 0.0, 'cost_3x' => 0, 'hrs_4x' => 0.0, 'cost_4x' => 0];

                foreach ($rows as $t) {
                    $c = $computeCost($t, $hourlyRate);
                    $totals['normal_mins'] += $c['normal_mins'];
                    $totals['ot_mins'] += $c['ot_mins'];
                    $totals['normal_cost'] += $c['normal_cost'];
                    $totals['ot_cost'] += $c['ot_cost'];
                    $totals['hrs_1_5x'] += $c['hrs_1_5x'];
                    $totals['cost_1_5x'] += $c['cost_1_5x'];
                    $totals['hrs_2x_wd'] += $c['hrs_2x_wd'];
                    $totals['cost_2x_wd'] += $c['cost_2x_wd'];
                    $totals['hrs_2x_we'] += $c['hrs_2x_we'];
                    $totals['cost_2x_we'] += $c['cost_2x_we'];
                    $totals['hrs_3x'] += $c['hrs_3x'];
                    $totals['cost_3x'] += $c['cost_3x'];
                    $totals['hrs_4x'] += $c['hrs_4x'];
                    $totals['cost_4x'] += $c['cost_4x'];
                    if ($c['ot_type'] === 'weekday') {
                        $totals['wd_hrs'] += $c['ot_hours'];
                        $totals['wd_cost'] += $c['ot_cost'];
                    }
                    if ($c['ot_type'] === 'weekend') {
                        $totals['we_hrs'] += $c['ot_hours'];
                        $totals['we_cost'] += $c['ot_cost'];
                    }
                }

                return [
                    'employee_id' => $emp?->id,
                    'name' => $emp?->name ?? '—',
                    'position' => $emp?->position ?? '—',
                    'initials' => strtoupper(substr($emp?->name ?? 'U', 0, 1)),
                    'sessions' => $rows->count(),
                    'hourly_rate' => $hourlyRate,
                    'normal_hours' => round($totals['normal_mins'] / 60, 2),
                    'ot_hours' => round($totals['ot_mins'] / 60, 2),
                    'hours' => round(($totals['normal_mins'] + $totals['ot_mins']) / 60, 2),
                    'normal_cost' => $totals['normal_cost'],
                    'ot_cost' => $totals['ot_cost'],
                    'labor_cost' => $totals['normal_cost'] + $totals['ot_cost'],
                    'wd_ot_hours' => round($totals['wd_hrs'], 2),
                    'wd_ot_cost' => $totals['wd_cost'],
                    'we_ot_hours' => round($totals['we_hrs'], 2),
                    'we_ot_cost' => $totals['we_cost'],
                    'hrs_1_5x' => round($totals['hrs_1_5x'], 2),
                    'cost_1_5x' => $totals['cost_1_5x'],
                    'hrs_2x_wd' => round($totals['hrs_2x_wd'], 2),
                    'cost_2x_wd' => $totals['cost_2x_wd'],
                    'hrs_2x_we' => round($totals['hrs_2x_we'], 2),
                    'cost_2x_we' => $totals['cost_2x_we'],
                    'hrs_3x' => round($totals['hrs_3x'], 2),
                    'cost_3x' => $totals['cost_3x'],
                    'hrs_4x' => round($totals['hrs_4x'], 2),
                    'cost_4x' => $totals['cost_4x'],
                ];
            })
            ->sortByDesc('hours')
            ->values();

        // ── Per-date work sessions (timeline) ─────────────────────────────────
        $workSessions = $timings
            ->groupBy(fn($t) => optional($t->tanggal)->format('d M Y') ?? '-')
            ->map(function ($rows, $date) {
                return [
                    'date' => $date,
                    'employees' => $rows->map(fn($t) => $t->employee?->name ?? '—')->unique()->values()->toArray(),
                    'hours' => round($rows->sum(fn($t) => max(0, ($t->duration_minutes ?? 0) - ($t->break_deducted_minutes ?? 0))) / 60, 2),
                    'sessions' => $rows->count(),
                ];
            })
            ->values();

        // ── Flat timing log rows ──────────────────────────────────────────────
        $timingRows = $timings
            ->map(function ($t) use ($byEmployee, $computeCost) {
                $emp = $byEmployee->firstWhere('employee_id', $t->employee_id);
                $hourlyRate = $emp['hourly_rate'] ?? 0;
                $c = $computeCost($t, $hourlyRate);
                $dayName = optional($t->tanggal)->isoFormat('ddd') ?? '—';

                return array_merge($c, [
                    'employee' => $t->employee?->name ?? '—',
                    'initials' => strtoupper(substr($t->employee?->name ?? 'U', 0, 1)),
                    'position' => $t->employee?->position ?? '—',
                    'day_name' => $dayName,
                    'date' => optional($t->tanggal)->format('d M Y') ?? '—',
                    'start_time' => $t->start_time ? \Carbon\Carbon::parse($t->start_time)->format('H:i') : '—',
                    'end_time' => $t->end_time ? \Carbon\Carbon::parse($t->end_time)->format('H:i') : '—',
                    'break_mins' => $t->break_deducted_minutes ?? 0,
                    'job_order' => $t->jobOrder?->name ?? '—',
                    'hourly_rate' => $hourlyRate,
                ]);
            })
            ->values();

        // ── Derived OT row collections ────────────────────────────────────────
        $weekdayOtRows = $timingRows->filter(fn($r) => $r['ot_type'] === 'weekday')->values();
        $weekendOtRows = $timingRows->filter(fn($r) => $r['ot_type'] === 'weekend')->values();
        $hasWdOt = $weekdayOtRows->isNotEmpty();
        $hasWeOt = $weekendOtRows->isNotEmpty();

        // ── Totals ────────────────────────────────────────────────────────────
        $totalLaborMinutes = $timings->sum(fn($t) => max(0, ($t->duration_minutes ?? 0) - ($t->break_deducted_minutes ?? 0)));
        $totalLaborHours = round($totalLaborMinutes / 60, 2);
        $totalOperators = $byEmployee->count();
        $avgHourlyRate = $byEmployee->avg('hourly_rate') ?? 0;
        $totalLaborCost = $byEmployee->sum('labor_cost');
        $totalNormalCost = $byEmployee->sum('normal_cost');
        $totalOtCost = $byEmployee->sum('ot_cost');
        $totalWdOtCost = $byEmployee->sum('wd_ot_cost');
        $totalWeOtCost = $byEmployee->sum('we_ot_cost');
        $totalWdOtHours = round($byEmployee->sum('wd_ot_hours'), 2);
        $totalWeOtHours = round($byEmployee->sum('we_ot_hours'), 2);

        $latestDate = $timings->sortByDesc('tanggal')->first()?->tanggal;
        $latestDateFmt = $latestDate ? $latestDate->format('d M Y') : '—';

        return view('finance.costing.workmanship-detail', compact('project', 'timingRows', 'byEmployee', 'workSessions', 'weekdayOtRows', 'weekendOtRows', 'hasWdOt', 'hasWeOt', 'totalLaborHours', 'totalOperators', 'avgHourlyRate', 'totalLaborCost', 'totalNormalCost', 'totalOtCost', 'totalWdOtCost', 'totalWeOtCost', 'totalWdOtHours', 'totalWeOtHours', 'latestDateFmt'));
    }

    /**
     * Full Freight Cost detail page.
     * Uses getCourierCosts() as single source of truth — same data as show() / main detail.
     */
    public function showFreightDetail($project_id)
    {
        $project = Project::where('id', $project_id)
            ->where(function ($q) {
                $q->where('project_status', 'Delivered')->orWhere('project_status', 'LIKE', '%WIP%');
            })
            ->firstOrFail();
        $project->load(['departments', 'jobOrders']);

        // Use the SAME getCourierCosts() as the main detail page
        $courierData = $this->getCourierCosts($project_id);

        // Split the combined couriers list into the two directions for the view
        $allCouriers = $courierData['couriers'];
        $sgBtShipments = $allCouriers->where('direction', 'SG → BT')->values();
        $btSgShipments = $allCouriers->where('direction', 'BT → SG')->values();

        $totalSgBtIDR = $sgBtShipments->sum('total_idr');
        $totalBtSgIDR = $btSgShipments->sum('total_idr');
        $totalFreightIDR = $courierData['total_idr'];
        $sgBtCount = $courierData['sg_bt_count'];
        $btSgCount = $courierData['bt_sg_count'];
        $sgdRate = \App\Models\Finance\Currency::where('name', 'SGD')->value('exchange_rate') ?? 12000;

        return view('finance.costing.freight-detail', compact('project', 'sgBtShipments', 'btSgShipments', 'totalSgBtIDR', 'totalBtSgIDR', 'totalFreightIDR', 'sgBtCount', 'btSgCount', 'courierData', 'sgdRate'));
    }

    public function viewCosting($project_id)
    {
        // Hanya project dengan project_status=Delivered yang bisa di-view costing
        $project = Project::where('id', $project_id)
            ->where(function ($q) {
                $q->where('project_status', 'Delivered')->orWhere('project_status', 'LIKE', '%WIP%');
            })
            ->firstOrFail();

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

        // Pre-build actual cost map from StockUsageBatch (exact batch price per GoodsOut)
        $actualCostMap = $this->buildActualCostMap($project_id);

        // Hitung total biaya per material dengan rumus baru dan konversi ke IDR
        $materials = $usages
            ->map(function ($usage) use ($actualCostMap) {
                $inventory = $usage->inventory;

                // Guard: inventory may be null (orphaned usage row or soft-deleted item)
                if (!$inventory) {
                    return null;
                }

                $usedQty = $usage->used_quantity ?? 0;

                // Unit name via single accessor
                $unitName = $inventory->unit_name ?: 'N/A';
                $costKey = $inventory->id . '_' . ($usage->job_order_id ?? 'null');
                $batchCostData = $actualCostMap[$costKey] ?? null;

                if ($batchCostData && $batchCostData['has_batch_data']) {
                    // Actual cost: sum of (qty_used × batch.unit_price × batch.currency.rate)
                    $totalCostInIDR = $batchCostData['total_cost_idr'];
                    $unitPriceIDR = $usedQty > 0 ? round($totalCostInIDR / $usedQty, 4) : 0;
                    $totalUnitCost = $unitPriceIDR;
                    $unitPrice = $unitPriceIDR;
                    $currency = (object) ['name' => 'IDR', 'exchange_rate' => 1];
                    $totalCost = $totalCostInIDR;
                } else {
                    // Fallback: weighted average across all active batches
                    $unitPrice = $inventory->price ?? 0;
                    $domesticFreight = $inventory->unit_domestic_freight_cost ?? 0;
                    $internationalFreight = $inventory->unit_international_freight_cost ?? 0;
                    $totalUnitCost = $unitPrice + $domesticFreight + $internationalFreight;
                    $currency = $inventory->currency ?? (object) ['name' => 'IDR', 'exchange_rate' => 1];
                    $exchangeRate = $currency->exchange_rate ?? 1;
                    $totalCost = $totalUnitCost * $usedQty;
                    $totalCostInIDR = $totalCost * $exchangeRate;
                }

                return (object) [
                    'job_order_name' => $usage->jobOrder?->name ?? 'No Job Order',
                    'inventory' => (object) [
                        'id' => $inventory->id ?? $usage->inventory_id,
                        'name' => $inventory->name ?? 'N/A',
                        'unit' => $unitName,
                        'price' => $unitPrice,
                        'total_unit_cost' => $totalUnitCost,
                        'currency' => $currency,
                    ],
                    'used_quantity' => $usedQty,
                    'total_price' => $totalCost,
                    'total_cost' => $totalCostInIDR,
                ];
            })
            ->filter()
            ->values(); // drop null rows (orphaned usages)

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
        $project = Project::where('id', $project_id)
            ->where(function ($q) {
                $q->where('project_status', 'Delivered')->orWhere('project_status', 'LIKE', '%WIP%');
            })
            ->firstOrFail();

        // ── 1. Material Cost rows ─────────────────────────────────────────────
        $usages = MaterialUsage::where('project_id', $project_id)
            ->with(['inventory.currency', 'inventory.unitRelation', 'jobOrder'])
            ->orderBy('job_order_id')
            ->get();

        // Pre-build actual cost map from StockUsageBatch for this project
        $actualCostMap = $this->buildActualCostMap($project_id);

        $materialRows = $usages
            ->map(function ($usage) use ($actualCostMap) {
                $inv = $usage->inventory;

                // Guard: inventory may be null (orphaned usage row or soft-deleted item)
                if (!$inv) {
                    return null;
                }

                $qty = $usage->used_quantity ?? 0;

                // ── Use actual batch cost if available ──
                $costKey = $inv->id . '_' . ($usage->job_order_id ?? 'null');
                $batchCostData = $actualCostMap[$costKey] ?? null;

                if ($batchCostData && $batchCostData['has_batch_data']) {
                    $totalIdr = $batchCostData['total_cost_idr'];
                    $unitPrice = $qty > 0 ? round($totalIdr / $qty, 4) : 0;
                    $domFreight = 0;
                    $intFreight = 0;
                    $totalUnit = $unitPrice;
                    $currency = (object) ['name' => 'IDR', 'exchange_rate' => 1];
                } else {
                    $unitPrice = $inv->price ?? 0;
                    $domFreight = $inv->unit_domestic_freight_cost ?? 0;
                    $intFreight = $inv->unit_international_freight_cost ?? 0;
                    $totalUnit = $unitPrice + $domFreight + $intFreight;
                    $currency = $inv->currency ?? (object) ['name' => 'IDR', 'exchange_rate' => 1];
                    $rate = $currency->exchange_rate ?? 1;
                    $totalIdr = $totalUnit * $qty * $rate;
                }

                // Unit name via single accessor
                $unitName = $inv->unit_name ?: 'N/A';

                return [
                    'job_order_name' => $usage->jobOrder?->name ?? 'No Job Order',
                    'material_name' => $inv->name ?? 'N/A',
                    'qty' => $qty,
                    'unit' => $unitName,
                    'currency' => strtoupper($currency->name ?? 'IDR'),
                    'unit_price' => $unitPrice,
                    'domestic_freight' => $domFreight,
                    'intl_freight' => $intFreight,
                    'total_unit_cost' => $totalUnit,
                    'total_idr' => $totalIdr,
                ];
            })
            ->filter()
            ->values()
            ->toArray(); // drop null rows (orphaned usages)

        // ── 2. Workmanship Cost rows ─────────────────────────────────────────
        $timings = \App\Models\Production\Timing::where('project_id', $project_id)
            ->where('status', 'complete')
            ->where('approval_status', 'approved')
            ->with(['jobOrder', 'employee'])
            ->orderBy('tanggal')
            ->orderBy('start_time')
            ->get();

        $workmanshipRows = $timings
            ->map(function ($t) {
                $emp = $t->employee;
                $hrs = round(($t->duration_minutes ?? 0) / 60, 2);
                $salary = $emp->salary ?? 0;
                $hourlyRate = $salary > 0 ? round($salary / 173, 0) : 0;
                $laborCost = round($hourlyRate * $hrs, 0);

                return [
                    'employee' => $emp?->name ?? '—',
                    'position' => $emp?->position ?? '—',
                    'date' => optional($t->tanggal)->format('d M Y') ?? '—',
                    'start_time' => $t->start_time ? \Carbon\Carbon::parse($t->start_time)->format('H:i') : '—',
                    'end_time' => $t->end_time ? \Carbon\Carbon::parse($t->end_time)->format('H:i') : '—',
                    'hours' => $hrs,
                    'job_order' => $t->jobOrder?->name ?? '—',
                    'step' => $t->step ?? '—',
                    'hourly_rate' => $hourlyRate,
                    'labor_cost' => $laborCost,
                ];
            })
            ->toArray();

        // ── 3. Freight Cost rows ─────────────────────────────────────────────
        $courierData = $this->getCourierCosts($project_id);
        $freightRows = $courierData['couriers']->toArray();

        // ── 4. Summary row (single project) ─────────────────────────────────
        $dcmCostings = \App\Models\Finance\DcmCosting::where('project_name', $project->name)->where('is_current', true)->get();
        $intlPoCostings = $dcmCostings->filter(fn($c) => str_contains(strtolower($c->purchase_type ?? ''), 'intl') || str_contains(strtolower($c->purchase_type ?? ''), 'international') || str_contains(strtolower($c->supplier ?? ''), 'sg') || str_contains(strtolower($c->department ?? ''), 'sg'));
        $localPoCostings = $dcmCostings->filter(fn($c) => !$intlPoCostings->contains('id', $c->id));
        $totalIntlPo = $intlPoCostings->sum('invoice_total');
        $totalLocalPo = $localPoCostings->sum('invoice_total');
        $usageIdr = collect($materialRows)->sum('total_idr');

        $summaryRows = [
            [
                'project_name' => $project->name,
                'type_dept' => $project->type_dept ?? '-',
                'sales' => $project->sales ?? '-',
                'deadline' => $project->deadline ? \Carbon\Carbon::parse($project->deadline)->format('d M Y') : '-',
                'intl_po' => $totalIntlPo,
                'local_po' => $totalLocalPo,
                'usage_idr' => $usageIdr,
            ],
        ];

        $filename = 'costing_' . \Illuminate\Support\Str::slug($project->name) . '_' . now()->format('Ymd') . '.xlsx';

        return Excel::download(new ProjectCostingMultiSheetExport($project->name, $summaryRows, $materialRows, $workmanshipRows, $freightRows, ['project' => $project->name]), $filename);
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
                    ->with(['inventory.currency', 'inventory.unitRelation'])
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

                    // Get unit name via single accessor (FK → fallback varchar)
                    $unitName = $inventory->unit_name ?: 'N/A';

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
                ->with(['inventory.currency', 'inventory.unitRelation', 'jobOrder'])
                ->get();

            // Pre-build actual cost map from StockUsageBatch
            $actualCostMap = $this->buildActualCostMap($project_id);

            // Calculate costs per material
            $materials = $usages->map(function ($usage) use ($actualCostMap) {
                $inventory = $usage->inventory;

                $usedQty = $usage->used_quantity ?? 0;

                // Unit name via single accessor
                $unitName = $inventory->unit_name ?: 'N/A';

                // ── Use actual batch cost if available ──
                $costKey = $inventory->id . '_' . ($usage->job_order_id ?? 'null');
                $batchCostData = $actualCostMap[$costKey] ?? null;

                if ($batchCostData && $batchCostData['has_batch_data']) {
                    $totalCostInIDR = $batchCostData['total_cost_idr'];
                    $unitPrice = $usedQty > 0 ? round($totalCostInIDR / $usedQty, 4) : 0;
                    $domesticFreight = 0;
                    $internationalFreight = 0;
                    $totalUnitCost = $unitPrice;
                    $currency = (object) ['name' => 'IDR', 'exchange_rate' => 1];
                    $totalCost = $totalCostInIDR;
                } else {
                    // Fallback: weighted average
                    $unitPrice = $inventory->price ?? 0;
                    $domesticFreight = $inventory->unit_domestic_freight_cost ?? 0;
                    $internationalFreight = $inventory->unit_international_freight_cost ?? 0;
                    $totalUnitCost = $unitPrice + $domesticFreight + $internationalFreight;
                    $currency = $inventory->currency ?? (object) ['name' => 'N/A', 'exchange_rate' => 1];
                    $exchangeRate = $currency->exchange_rate ?? 1;
                    $totalCost = $totalUnitCost * $usedQty;
                    $totalCostInIDR = $totalCost * $exchangeRate;
                }

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
     * Get freight costs for a specific project.
     * Source: BT-SG and SG-BT Lark item tracking tables, using only sgd_cost column.
     * SGD → IDR conversion uses the exchange rate from Finance Currency module.
     */
    private function getCourierCosts($projectId)
    {
        // ── SGD exchange rate from Currency module (single source of truth) ──
        $sgdRate = \App\Models\Finance\Currency::where('name', 'SGD')->value('exchange_rate') ?? 12000;

        // Get ALL BT-SG and SG-BT items for this project
        $btSgItems = LarkBtSgItemTracking::where('project_id', $projectId)->get();
        $sgBtItems = LarkSgBtItemTracking::where('project_id', $projectId)->get();

        // Sum SGD costs per direction
        $btSgTotalSgd = round((float) $btSgItems->sum('sgd_cost'), 2);
        $sgBtTotalSgd = round((float) $sgBtItems->sum('sgd_cost'), 2);
        $totalSgd = round($btSgTotalSgd + $sgBtTotalSgd, 2);
        $totalIdr = round($totalSgd * $sgdRate, 0);
        $btSgTotalIdr = round($btSgTotalSgd * $sgdRate, 0);
        $sgBtTotalIdr = round($sgBtTotalSgd * $sgdRate, 0);

        // Build item lists per direction for detail display
        $btSgList = $btSgItems
            ->map(
                fn($i) => [
                    'name' => $i->item_name ?? '—',
                    'qty' => $i->qty ?? 1,
                    'status' => $i->status ?? 'pending',
                    'sgd_cost' => $i->sgd_cost ?? 0,
                ],
            )
            ->values()
            ->toArray();

        $sgBtList = $sgBtItems
            ->map(
                fn($i) => [
                    'name' => $i->item_name ?? '—',
                    'qty' => $i->qty ?? 1,
                    'status' => $i->status ?? 'pending',
                    'sgd_cost' => $i->sgd_cost ?? 0,
                ],
            )
            ->values()
            ->toArray();

        // Build direction groups (replaces courier groups) for view consumption
        $groups = collect();
        if ($btSgItems->isNotEmpty()) {
            $groups->push([
                'direction' => 'BT → SG',
                'items_count' => $btSgItems->count(),
                'items' => $btSgList,
                'total_sgd' => $btSgTotalSgd,
                'total_idr' => $btSgTotalIdr,
            ]);
        }
        if ($sgBtItems->isNotEmpty()) {
            $groups->push([
                'direction' => 'SG → BT',
                'items_count' => $sgBtItems->count(),
                'items' => $sgBtList,
                'total_sgd' => $sgBtTotalSgd,
                'total_idr' => $sgBtTotalIdr,
            ]);
        }

        return [
            'bt_sg_count' => $btSgItems->count(),
            'sg_bt_count' => $sgBtItems->count(),
            'total_couriers' => $groups->count(),
            'total_items' => $btSgItems->count() + $sgBtItems->count(),
            'total_sgd' => $totalSgd,
            'total_idr' => $totalIdr,
            'couriers' => $groups,
        ];
    }

    /**
     * Build a map of ACTUAL material costs (IDR) from StockUsageBatch records.
     * Uses the exact batch unit_price × batch currency exchange_rate actually consumed
     * during Goods Out — no weighted average across unrelated batches.
     *
     * Key: "{inventory_id}_{job_order_id|null}"
     * Value: ['total_cost_idr' => float, 'has_batch_data' => bool]
     *
     * Falls back gracefully: if a GoodsOut has no StockUsageBatch rows the cost
     * contribution from that GoodsOut is 0 (caller should fall back to inventory->price).
     */
    private function buildActualCostMap(int $projectId): array
    {
        $goodsOuts = \App\Models\Logistic\GoodsOut::where('project_id', $projectId)
            ->whereNull('deleted_at')
            ->with(['stockUsageBatches.batch.currency'])
            ->get();

        $map = [];

        foreach ($goodsOuts as $go) {
            $key = $go->inventory_id . '_' . ($go->job_order_id ?? 'null');

            if (!isset($map[$key])) {
                $map[$key] = ['total_cost_idr' => 0.0, 'has_batch_data' => false];
            }

            foreach ($go->stockUsageBatches as $sub) {
                $batch = $sub->batch;
                if (!$batch) {
                    continue;
                }
                $rate = $batch->currency?->exchange_rate ?? 1;
                $map[$key]['total_cost_idr'] += (float) $sub->qty_used * (float) $batch->unit_price * (float) $rate;
                $map[$key]['has_batch_data'] = true;
            }
        }

        return $map;
    }
}

// If you want to log $materials, place this inside a controller method where $materials is defined, for example:
// Log::info('Material Data:', $materials->toArray());
