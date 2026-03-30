<?php

namespace App\Http\Controllers\Hr;

use App\Exports\SymcoreExport;
use App\Http\Controllers\Controller;
use App\Models\Admin\Department;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class SymcoreExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!in_array(Auth::user()->role, ['super_admin', 'admin_hr', 'admin'])) {
                abort(403);
            }
            return $next($request);
        });
    }

    public function index()
    {
        $departments  = Department::orderBy('name')->get();
        $currentMonth = now()->month;
        $currentYear  = now()->year;

        return view('hr.symcore-export.index', compact('departments', 'currentMonth', 'currentYear'));
    }

    public function export(Request $request)
    {
        $request->validate([
            'month'         => 'nullable|integer|min:1|max:12',
            'year'          => 'nullable|integer|min:2020|max:2099',
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
            'department_id' => 'nullable|integer|exists:departments,id',
        ]);

        // Tentukan range tanggal:
        // Prioritas: start_date/end_date → month/year → bulan ini
        if ($request->filled('start_date') || $request->filled('end_date')) {
            $startDate = $request->filled('start_date')
                ? Carbon::parse($request->start_date)->format('Y-m-d')
                : Carbon::now()->startOfMonth()->format('Y-m-d');

            $endDate = $request->filled('end_date')
                ? Carbon::parse($request->end_date)->format('Y-m-d')
                : Carbon::now()->endOfMonth()->format('Y-m-d');
        } else {
            $month     = $request->filled('month') ? (int) $request->month : now()->month;
            $year      = $request->filled('year')  ? (int) $request->year  : now()->year;
            $startDate = Carbon::create($year, $month, 1)->startOfMonth()->format('Y-m-d');
            $endDate   = Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');
        }

        $deptId   = $request->filled('department_id') ? (int) $request->department_id : null;
        $filename = 'Symcore_Import_' . str_replace('-', '', $startDate) . '_' . str_replace('-', '', $endDate) . '.xlsx';

        return Excel::download(new SymcoreExport($startDate, $endDate, $deptId), $filename);
    }
}
