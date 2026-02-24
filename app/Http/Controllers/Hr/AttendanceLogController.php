<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hr\Employee;
use App\Models\Hr\AttendanceLog;
use App\Models\Hr\DailyAttendance;
use App\Models\Hr\LeaveRequest;
use App\Imports\AttendancesImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AttendanceLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $rolesAllowed = ['super_admin', 'admin_hr', 'admin'];
            if (!in_array(Auth::user()->role, $rolesAllowed)) {
                abort(403);
            }
            return $next($request);
        });
    }

    /**
     * Menampilkan daftar kehadiran harian
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $employeesQuery = Employee::where('status', 'active');
        if (!empty($search)) {
            $employeesQuery->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('employee_no', 'LIKE', "%{$search}%");
            });
        }
        $employees = $employeesQuery->orderBy('name')->get();

        if ($request->has('all')) {
            $datesWithData = AttendanceLog::select('date')->distinct()->pluck('date')->toArray();
            $datesWithData = array_merge($datesWithData, DailyAttendance::select('date')->distinct()->pluck('date')->toArray());
            $datesWithData = array_unique($datesWithData);
            sort($datesWithData);
            $dates = array_map(function ($d) {
                return Carbon::parse($d);
            }, $datesWithData);
        }
        elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $dates = [];
            for ($d = $startDate->copy(); $d->lte($endDate); $d->addDay()) {
                $dates[] = $d->copy();
            }
        }
        else {
            $date = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();
            $dates = [$date];
        }

        $attendances = collect();

        foreach ($dates as $currentDate) {
            $dateStr = $currentDate->format('Y-m-d');

            foreach ($employees as $employee) {
                $daily = DailyAttendance::where('employee_id', $employee->id)
                    ->where('date', $dateStr)
                    ->first();

                if ($daily) {
                    $attendances->push((object)[
                        'employee' => $employee,
                        'date' => $currentDate,
                        'clock_in' => $daily->clock_in ? Carbon::parse($daily->clock_in) : null,
                        'clock_out' => $daily->clock_out ? Carbon::parse($daily->clock_out) : null,
                        'total_hours' => $daily->total_hours,
                        'status' => $daily->status,
                        'remarks' => $daily->remarks,
                        'is_corrected' => true,
                        'daily_id' => $daily->id,
                    ]);
                } else {
                    $logs = AttendanceLog::where('employee_id', $employee->id)
                        ->whereDate('date', $dateStr)
                        ->orderBy('clock_in')
                        ->get();

                    if ($logs->isNotEmpty()) {
                        $clockIn = $logs->min('clock_in');
                        $clockOut = $logs->max('clock_out');
                        $status = $this->determineStatus($employee, $currentDate, $clockIn, $clockOut);
                        $totalHours = $clockIn && $clockOut ? $this->calculateHours($clockIn, $clockOut) : null;

                        $attendances->push((object)[
                            'employee' => $employee,
                            'date' => $currentDate,
                            'clock_in' => $clockIn ? Carbon::parse($clockIn) : null,
                            'clock_out' => $clockOut ? Carbon::parse($clockOut) : null,
                            'total_hours' => $totalHours,
                            'status' => $status,
                            'remarks' => null,
                            'is_corrected' => false,
                            'daily_id' => null,
                        ]);
                    } else {
                        $leave = LeaveRequest::where('employee_id', $employee->id)
                            ->where('start_date', '<=', $dateStr)
                            ->where('end_date', '>=', $dateStr)
                            ->where('approval_1', 'approved')
                            ->where('approval_2', 'approved')
                            ->first();

                        $status = $leave ? $this->mapLeaveTypeToStatus($leave->type) : 'Alpha';
                        $remarks = $leave ? $leave->reason : null;

                        $attendances->push((object)[
                            'employee' => $employee,
                            'date' => $currentDate,
                            'clock_in' => null,
                            'clock_out' => null,
                            'total_hours' => null,
                            'status' => $status,
                            'remarks' => $remarks,
                            'is_corrected' => false,
                            'daily_id' => null,
                        ]);
                    }
                }
            }
        }

        $attendances = $attendances->sortByDesc('date')->sortBy('employee.name')->values();

        $perPage = 50;
        $currentPage = $request->get('page', 1);
        $pagedData = $attendances->forPage($currentPage, $perPage);
        $attendancesPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedData,
            $attendances->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $latestImportSource = null;
        if (!$request->has('all') && !$request->filled('start_date') && !$request->filled('end_date')) {
            $latest = AttendanceLog::latest('created_at')->first();
            if ($latest) {
                $latestImportSource = $latest->import_source;
            }
        }

        return view('hr.attendance-logs.index', compact('attendancesPaginated', 'employees', 'latestImportSource', 'search'));
    }

    private function determineStatus($employee, $date, $clockIn, $clockOut)
    {
        if (!$clockIn || !$clockOut) {
            return 'Alpha';
        }

        $policy = $employee->workPolicy;
        if (!$policy) {
            return 'Present';
        }

        $dayOfWeek = strtolower($date->format('l'));
        $standardStart = $policy->{$dayOfWeek . '_start'};

        if (!$standardStart) {
            return 'Present';
        }

        $toleranceMinutes = 4;
        $clockInTime = Carbon::parse($clockIn);
        $standardStartTime = Carbon::parse($standardStart);

        if ($clockInTime->gt($standardStartTime->addMinutes($toleranceMinutes))) {
            return 'Late';
        }

        return 'Present';
    }

    private function calculateHours($clockIn, $clockOut)
    {
        return Carbon::parse($clockIn)->diffInMinutes(Carbon::parse($clockOut)) / 60;
    }

    private function mapLeaveTypeToStatus($type)
    {
        $map = [
            'ANNUAL' => 'Annual Leave',
            'MATERNITY' => 'Maternity Leave',
            'WEDDING' => 'Wedding Leave',
            'SONWED' => 'Son\'s Wedding Leave',
        ];
        return $map[$type] ?? 'Excused';
    }

    public function edit($employeeId, $date)
    {
        $employee = Employee::findOrFail($employeeId);
        $date = Carbon::parse($date);

        $attendance = DailyAttendance::firstOrNew([
            'employee_id' => $employeeId,
            'date' => $date->format('Y-m-d'),
        ]);

        if (!$attendance->exists) {
            $logs = AttendanceLog::where('employee_id', $employeeId)
                ->whereDate('date', $date)
                ->orderBy('clock_in')
                ->get();
            if ($logs->isNotEmpty()) {
                $attendance->clock_in = $logs->min('clock_in');
                $attendance->clock_out = $logs->max('clock_out');
            }
        }

        return view('hr.attendance-logs.edit', compact('employee', 'date', 'attendance'));
    }

    public function update(Request $request, $employeeId, $date)
    {
        $request->validate([
            'clock_in' => 'nullable|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i|after:clock_in',
            'status' => 'required|in:Present,Late,Excused,Sick Leave,Annual Leave,Alpha',
            'remarks' => 'nullable|string',
        ]);

        $employee = Employee::findOrFail($employeeId);
        $date = Carbon::parse($date);

        $attendance = DailyAttendance::updateOrCreate(
            ['employee_id' => $employeeId, 'date' => $date->format('Y-m-d')],
            [
                'clock_in' => $request->clock_in,
                'clock_out' => $request->clock_out,
                'status' => $request->status,
                'remarks' => $request->remarks,
                'updated_by' => Auth::id(),
            ]
        );

        $this->calculateAttendanceFields($attendance);

        return redirect()->route('attendance-logs.index', ['date' => $date->format('Y-m-d')])
            ->with('success', 'Attendance data updated successfully.');
    }

    private function calculateAttendanceFields(DailyAttendance $attendance)
    {
        $employee = $attendance->employee;
        $policy = $employee->workPolicy;
        if (!$policy) {
            return;
        }

        $monthlySalary = $employee->salary ?? 0;
        $hourlyRate = $monthlySalary > 0 ? $monthlySalary / 173 : 0;

        $dayOfWeek = strtolower($attendance->date->format('l'));
        $standardStart = $policy->{$dayOfWeek . '_start'};
        $standardEnd = $policy->{$dayOfWeek . '_end'};

        if ($attendance->clock_in && $attendance->clock_out && $standardStart && $standardEnd) {
            $start = Carbon::parse($attendance->clock_in);
            $end = Carbon::parse($attendance->clock_out);
            $standardStartTime = Carbon::parse($standardStart);
            $standardEndTime = Carbon::parse($standardEnd);

            $attendance->total_hours = $end->diffInMinutes($start) / 60;

            if ($start->gt($standardStartTime)) {
                $lateMinutes = $standardStartTime->diffInMinutes($start);
                $attendance->late_minutes = $lateMinutes;
                if ($lateMinutes >= 4) {
                    $attendance->late_deduction = 25000;
                    $extraMinutes = $lateMinutes - 4;
                    if ($extraMinutes > 0) {
                        $attendance->late_deduction += ($extraMinutes / 60) * $hourlyRate;
                    }
                }
            }

            if ($end->lt($standardEndTime)) {
                $earlyMinutes = $end->diffInMinutes($standardEndTime);
                $attendance->early_leave_minutes = $earlyMinutes;
                $attendance->early_leave_deduction = ($earlyMinutes / 60) * $hourlyRate;
            }

            if ($end->gt($standardEndTime)) {
                $overtimeMinutes = $standardEndTime->diffInMinutes($end);
                $attendance->overtime_minutes = $overtimeMinutes;
                $attendance->overtime_pay = ($overtimeMinutes / 60) * $hourlyRate * 1.5;
            }
        }

        $attendance->save();
    }

    public function storeImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimetypes:application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/csv,text/csv,text/plain,application/octet-stream|max:10240',
        ]);

        $file = $request->file('file');
        $originalExtension = $file->getClientOriginalExtension();
        $tempFile = null;
        $importFile = $file->getPathname();
        $importSource = $file->getClientOriginalName();

        try {
            if (strtolower($originalExtension) === 'xls') {
                try {
                    Log::info('Attempting to convert XLS file: ' . $file->getClientOriginalName());

                    $spreadsheet = IOFactory::load($file->getPathname());
                    $tempPath = tempnam(sys_get_temp_dir(), 'converted_') . '.xlsx';
                    $writer = new Xlsx($spreadsheet);
                    $writer->save($tempPath);

                    $tempFile = $tempPath;
                    $importFile = $tempPath;
                    $importSource = $file->getClientOriginalName() . ' (converted to xlsx)';

                    Log::info('Conversion successful: ' . $tempPath);
                } catch (\Exception $e) {
                    Log::error('Conversion failed: ' . $e->getMessage());

                    if ($tempFile && file_exists($tempFile)) {
                        unlink($tempFile);
                    }

                    return response()->json([
                        'success' => false,
                        'message' => 'File .xls cannot be read. Please open the file with Microsoft Excel, save it as .xlsx format, and try again.'
                    ], 422);
                }
            }

            $import = new AttendancesImport($importSource);
            Excel::import($import, $importFile);

            $success = $import->getSuccessCount();
            $failed = $import->getFailedRows();

            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }

            $minDate = null;
            $maxDate = null;
            if ($success > 0) {
                $dates = AttendanceLog::where('import_source', $importSource)->distinct()->pluck('date');
                Log::info('Dates to generate daily: ' . $dates->implode(', '));

                foreach ($dates as $date) {
                    $this->generateDailyForDate(Carbon::parse($date));
                }
                $minDate = $dates->min();
                $maxDate = $dates->max();
            }

            if (count($failed) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Import completed with errors. Success: {$success}, Failed: " . count($failed),
                    'failed_rows' => $failed
                ], 422);
            }

            $redirectUrl = $minDate && $maxDate
                ? route('attendance-logs.index', ['start_date' => $minDate, 'end_date' => $maxDate])
                : route('attendance-logs.index');

            return response()->json([
                'success' => true,
                'message' => "All {$success} data imported successfully.",
                'redirect_url' => $redirectUrl
            ]);

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];
            foreach ($failures as $failure) {
                $errorMessages[] = "Row " . $failure->row() . ": " . implode(', ', $failure->errors());
            }

            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }

            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode('; ', $errorMessages)
            ], 422);

        } catch (\Exception $e) {
            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }

            Log::error('Import error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateDaily(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
        ]);

        $date = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();
        $this->generateDailyForDate($date);

        return redirect()->back()->with('success', 'Daily data updated for ' . $date->format('d-m-Y'));
    }

    private function generateDailyForDate(Carbon $date)
    {
        Log::info("===== GENERATING DAILY FOR DATE: " . $date->format('Y-m-d') . " =====");

        $employees = Employee::where('status', 'active')->get();
        $dateStr = $date->format('Y-m-d');

        foreach ($employees as $employee) {
            $logs = AttendanceLog::where('employee_id', $employee->id)
                ->whereDate('date', $dateStr)
                ->orderBy('clock_in')
                ->get();

            Log::info("Employee {$employee->id} ({$employee->name}) - Logs found: " . $logs->count());

            if ($logs->isNotEmpty()) {
                $clockIn = $logs->min('clock_in');
                $clockOut = $logs->max('clock_out');
                $status = $this->determineStatus($employee, $date, $clockIn, $clockOut);
                $totalHours = $clockIn && $clockOut ? $this->calculateHours($clockIn, $clockOut) : null;

                $clockInFormatted = $clockIn ? Carbon::parse($clockIn)->format('H:i:s') : null;
                $clockOutFormatted = $clockOut ? Carbon::parse($clockOut)->format('H:i:s') : null;

                Log::info("   -> Prepared: clock_in={$clockInFormatted}, clock_out={$clockOutFormatted}, total_hours={$totalHours}, status={$status}");

                try {
                    $daily = DailyAttendance::updateOrCreate(
                        ['employee_id' => $employee->id, 'date' => $dateStr],
                        [
                            'clock_in' => $clockInFormatted,
                            'clock_out' => $clockOutFormatted,
                            'total_hours' => $totalHours,
                            'status' => $status,
                            'remarks' => null,
                            'updated_by' => Auth::id(),
                        ]
                    );

                    if ($clockIn && $clockOut) {
                        $this->calculateAttendanceFields($daily);
                    }

                    Log::info("   ✅ Successfully saved daily for employee {$employee->id}");
                } catch (\Exception $e) {
                    Log::error("   ❌ Failed to save daily for employee {$employee->id}: " . $e->getMessage());
                }
            } else {
                $leave = LeaveRequest::where('employee_id', $employee->id)
                    ->where('start_date', '<=', $dateStr)
                    ->where('end_date', '>=', $dateStr)
                    ->where('approval_1', 'approved')
                    ->where('approval_2', 'approved')
                    ->first();

                $status = $leave ? $this->mapLeaveTypeToStatus($leave->type) : 'Alpha';
                $remarks = $leave ? $leave->reason : null;

                try {
                    DailyAttendance::updateOrCreate(
                        ['employee_id' => $employee->id, 'date' => $dateStr],
                        [
                            'clock_in' => null,
                            'clock_out' => null,
                            'total_hours' => null,
                            'status' => $status,
                            'remarks' => $remarks,
                            'updated_by' => Auth::id(),
                        ]
                    );
                    Log::info("   ✅ Saved alpha record for employee {$employee->id}");
                } catch (\Exception $e) {
                    Log::error("   ❌ Failed to save alpha for employee {$employee->id}: " . $e->getMessage());
                }
            }
        }
        Log::info("===== FINISHED GENERATING DAILY FOR DATE: " . $date->format('Y-m-d') . " =====\n");
    }

    public function export(Request $request)
    {
        $query = AttendanceLog::with('employee')
            ->activeEmployees();

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        if ($logs->isEmpty()) {
            return redirect()->back()->with('warning', 'No data to export.');
        }

        $filename = 'attendance_logs_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new class($logs) implements FromCollection, WithHeadings, WithMapping {
            private $logs;

            public function __construct($logs)
            {
                $this->logs = $logs;
            }

            public function collection()
            {
                return $this->logs;
            }

            public function headings(): array
            {
                return [
                    'Employee No',
                    'Employee Name',
                    'Date',
                    'Clock In',
                    'Clock Out',
                    'Total Hours',
                ];
            }

            public function map($log): array
            {
                return [
                    $log->employee->employee_no ?? '-',
                    $log->employee->name ?? '-',
                    $log->date->format('Y-m-d'),
                    $log->clock_in ? Carbon::parse($log->clock_in)->format('H:i') : '-',
                    $log->clock_out ? Carbon::parse($log->clock_out)->format('H:i') : '-',
                    $log->total_hours ? number_format($log->total_hours, 2) . ' hrs' : '-',
                ];
            }
        }, $filename);
    }
}