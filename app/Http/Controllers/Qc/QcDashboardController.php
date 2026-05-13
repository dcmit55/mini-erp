<?php

namespace App\Http\Controllers\Qc;

use App\Http\Controllers\Controller;
use App\Models\Qc\QcProject;
use App\Models\Qc\QcRejectLog;
use Illuminate\Http\JsonResponse;

class QcDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $projects = QcProject::with(['checklistItems', 'rejectLogs', 'dailyProgress'])
            ->orderByDesc('created_at')
            ->get();

        $totalProjects   = $projects->count();
        $wip             = $projects->where('status', 'WIP')->count();
        $delivered       = $projects->where('status', 'Delivered')->count();
        $rejected        = $projects->where('status', 'Rejected')->count();

        $allRejects = $projects->flatMap->rejectLogs;
        $totalRejects  = $allRejects->count();
        $activeRejects = $allRejects->whereIn('rework_status', ['OPEN', 'IN_REPAIR'])->count();
        $closedRejects = $allRejects->where('rework_status', 'CLOSED')->count();
        $closureRate   = $totalRejects > 0 ? round($closedRejects / $totalRejects * 100) : 0;
        $avgProgress   = $totalProjects > 0 ? round($projects->sum('progress') / $totalProjects) : 0;

        // Stage progress aggregates (for WIP projects)
        $wipProjects   = $projects->where('status', 'WIP');
        $wipCount      = $wipProjects->count();
        $avgCutting    = $wipCount > 0
            ? round($wipProjects->avg(fn($p) => $p->stage_progress['cutting'] ?? 0))
            : 0;
        $avgSewing     = $wipCount > 0
            ? round($wipProjects->avg(fn($p) => $p->stage_progress['sewing'] ?? 0))
            : 0;
        $avgFinishing  = $wipCount > 0
            ? round($wipProjects->avg(fn($p) => $p->stage_progress['finishing'] ?? 0))
            : 0;

        // Data untuk charts
        $statusDist = [
            'WIP'       => $wip,
            'Delivered' => $delivered,
            'Rejected'  => $rejected,
        ];

        $projectsData = $projects->map(fn($p) => [
            'uid'           => $p->uid,
            'job_number'    => $p->job_number,
            'project_name'  => $p->project_name,
            'mascot_type'   => $p->mascot_type,
            'status'        => $p->status,
            'progress'      => $p->progress,
            'total_unit'    => $p->total_unit,
            'supervisor'    => $p->creator?->name ?? '—',
            'deadline'      => $p->deadline?->toDateString(),
            'created_at'    => $p->created_at->toDateString(),
            'stage_progress'  => $p->stage_progress ?? ['cutting' => 0, 'sewing' => 0, 'finishing' => 0],
            'checklist_pass'  => $p->checklistItems->where('status', 'PASS')->count(),
            'checklist_fail'  => $p->checklistItems->where('status', 'FAIL')->count(),
            'checklist_total' => $p->checklistItems->count(),
            'open_defects'    => $p->rejectLogs->where('rework_status', 'OPEN')->count(),
            'total_defects'   => $p->rejectLogs->count(),
        ]);

        // Defect categories (top 7)
        $catMap = $allRejects->groupBy('defect_category')
            ->map->count()
            ->sortDesc()
            ->take(7);

        // Monthly trend
        $monthlyTrend = $allRejects
            ->filter(fn($r) => !empty($r->created_at))
            ->groupBy(fn($r) => $r->created_at->format('Y-m'))
            ->map->count()
            ->sortKeys();

        return response()->json([
            'kpi' => [
                'total_projects' => $totalProjects,
                'wip'            => $wip,
                'delivered'      => $delivered,
                'rejected'       => $rejected,
                'active_defects' => $activeRejects,
                'total_defects'  => $totalRejects,
                'closure_rate'   => $closureRate,
                'avg_progress'   => $avgProgress,
                'avg_cutting'    => $avgCutting,
                'avg_sewing'     => $avgSewing,
                'avg_finishing'  => $avgFinishing,
            ],
            'charts' => [
                'status_dist'   => $statusDist,
                'defect_cats'   => $catMap,
                'monthly_trend' => $monthlyTrend,
            ],
            'projects' => $projectsData,
        ]);
    }
}
