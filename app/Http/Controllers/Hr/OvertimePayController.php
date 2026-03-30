<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\OvertimePayDetail;
use App\Models\Hr\OvertimeRequest;
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

        // Total amount selalu bulan berjalan (tidak terpengaruh filter)
        $totalAmount = OvertimePayDetail::whereMonth('calculated_at', now()->month)
                        ->whereYear('calculated_at', now()->year)
                        ->sum('total_pay');

        $employees = Employee::select('id', 'name')->get();

        return view('hr.overtime-pays.index', compact('payDetails', 'employees', 'totalAmount'));
    }

    public function create()
    {
        if (!in_array(auth()->user()->role, ['hr', 'admin_hr', 'super_admin', 'admin'])) {
            abort(403);
        }

        $pendingRequests = OvertimeRequest::with('employee')
            ->where('status', 'approved')
            ->whereDoesntHave('payDetail')
            ->latest()
            ->get();

        return view('hr.overtime-pays.create', compact('pendingRequests'));
    }

    public function store(Request $request)
    {
        if (!in_array(auth()->user()->role, ['hr', 'admin_hr', 'super_admin', 'admin'])) {
            abort(403);
        }

        $request->validate([
            'request_ids'   => 'required|array|min:1',
            'request_ids.*' => 'exists:overtime_requests,id',
        ]);

        $calculated = 0;
        $errors     = [];

        foreach ($request->request_ids as $id) {
            $ot = OvertimeRequest::with('employee')->find($id);

            if (!$ot || $ot->status !== 'approved') {
                $errors[] = "ID {$id}: request belum approved.";
                continue;
            }

            if ($ot->payDetail()->exists()) {
                $errors[] = "ID {$id}: sudah pernah dikalkulasi.";
                continue;
            }

            try {
                $ot->calculateAndSavePayDetail();
                $calculated++;
            } catch (\Exception $e) {
                $errors[] = "ID {$id} ({$ot->employee->name}): " . $e->getMessage();
            }
        }

        $message = "{$calculated} kalkulasi berhasil disimpan.";
        if ($errors) {
            $message .= ' Gagal: ' . implode(', ', $errors);
            return redirect()->route('overtime-pays.index')->with('error', $message);
        }

        return redirect()->route('overtime-pays.index')->with('success', $message);
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