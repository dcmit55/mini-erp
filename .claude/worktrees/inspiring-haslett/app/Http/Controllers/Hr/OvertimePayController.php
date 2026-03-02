<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\OvertimePayDetail;
use App\Models\Hr\Employee;
use Illuminate\Http\Request;

class OvertimePayController extends Controller
{
    public function index(Request $request)
    {
        if (!in_array(auth()->user()->role, ['hr', 'admin_hr', 'super_admin', 'admin'])) {
            abort(403);
        }

        $query = OvertimePayDetail::with(['overtimeRequest', 'employee']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('calculated_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        $payDetails = $query->latest('calculated_at')->paginate(15);
        
        // Hitung total amount dari query yang sama (setelah filter)
        $totalAmount = $query->sum('total_pay');

        $employees = Employee::select('id', 'name')->get();

        return view('hr.overtime-pays.index', compact('payDetails', 'employees', 'totalAmount'));
    }

    public function show($id)
    {
        $payDetail = OvertimePayDetail::with(['overtimeRequest', 'employee'])->findOrFail($id);
        return view('hr.overtime-pays.show', compact('payDetail'));
    }

    public function destroy($id)
    {
        $payDetail = OvertimePayDetail::findOrFail($id);
        $payDetail->delete();

        return redirect()->route('overtime-pays.index')
            ->with('success', 'Calculation deleted successfully.');
    }
}