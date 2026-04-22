<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hr\Employee;
use App\Models\Hr\AttendanceLog;
use App\Models\Hr\DailyAttendance;
use App\Models\Hr\LeaveRequest;
use App\Models\Hr\SessionShift;
use App\Models\Admin\Department;
use App\Imports\AttendancesImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\DailyAttendanceService;
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
        $this->middleware('can:hr.attendance.view');
    }

    /**
     * Menampilkan daftar kehadiran harian
     */
    public function index(Request $request)
    {
        $search       = $request->input('search', '');
        $departmentId = $request->input('department_id');

        $employeesQuery = Employee::where('status', 'active');
        if (!empty($search)) {
            $employeesQuery->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('employee_no', 'LIKE', "%{$search}%");
            });
        }
        if ($departmentId) {
            $employeesQuery->where('department_id', $departmentId);
        }
        $employees   = $employeesQuery->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        if ($request->has('all')) {
            $datesWithData = AttendanceLog::select('date')->distinct()->pluck('date')->toArray();
            $datesWithData = array_merge($datesWithData, DailyAttendance::select('date')->distinct()->pluck('date')->toArray());
            $datesWithData = array_unique($datesWithData);
            sort($datesWithData);
            $dates = array_map(function ($d) {
                return Carbon::parse($d);
            }, $datesWithData);
        }
        elseif ($request->filled('start_date')) {
            $startDate = Carbon::parse($request->start_date);
            $endDate   = $request->filled('end_date') ? Carbon::parse($request->end_date) : $startDate->copy();
            $dates = [];
            for ($d = $startDate->copy(); $d->lte($endDate); $d->addDay()) {
                $dates[] = $d->copy();
            }
        }
        else {
            $dates = [Carbon::today()];
        }

        // ── Batch-load semua data yang dibutuhkan (hindari N+1) ──────────────────
        $employeeIds = $employees->pluck('id')->toArray();
        $dateStrs    = array_map(fn($d) => $d->format('Y-m-d'), $dates);
        $minDate     = min($dateStrs);
        $maxDate     = max($dateStrs);

        // 1 query: semua daily_attendances untuk employee+tanggal yang relevan
        $dailiesMap = DailyAttendance::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$minDate, $maxDate])
            ->with('sessionShift')
            ->get()
            ->groupBy(fn($d) => $d->employee_id . '_' . $d->date->format('Y-m-d'));

        // 1 query: semua attendance_logs
        $logsMap = AttendanceLog::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$minDate, $maxDate])
            ->get()
            ->groupBy(fn($l) => $l->employee_id . '_' . Carbon::parse($l->date)->format('Y-m-d'));

        // 1 query: semua cuti yang approved dalam rentang tanggal
        $leavesMap = LeaveRequest::whereIn('employee_id', $employeeIds)
            ->where('start_date', '<=', $maxDate)
            ->where('end_date', '>=', $minDate)
            ->where('approval_1', 'approved')
            ->where('approval_2', 'approved')
            ->get()
            ->groupBy('employee_id');

        $attendanceService = app(DailyAttendanceService::class);
        $attendances       = collect();

        foreach ($dates as $currentDate) {
            $dateStr = $currentDate->format('Y-m-d');

            foreach ($employees as $employee) {
                $key   = $employee->id . '_' . $dateStr;
                $daily = $dailiesMap->get($key)?->first();

                if ($daily) {
                    $attendances->push((object)[
                        'employee'     => $employee,
                        'date'         => $currentDate,
                        'clock_in'     => $daily->clock_in  ? Carbon::parse($daily->clock_in)  : null,
                        'clock_out'    => $daily->clock_out ? Carbon::parse($daily->clock_out) : null,
                        'total_hours'  => $daily->total_hours,
                        'status'       => $daily->status,
                        'remarks'      => $daily->remarks,
                        'is_corrected' => true,
                        'daily_id'     => $daily->id,
                        'session_shift'=> $daily->sessionShift,
                    ]);
                } else {
                    $employeeLogs = $logsMap->get($key, collect());

                    if ($employeeLogs->isNotEmpty()) {
                        $clockIn  = $employeeLogs->min('clock_in');
                        $clockOut = $employeeLogs->max('clock_out');
                        $status   = $attendanceService->determineStatus($employee, $currentDate, $clockIn, $clockOut);
                        $remarks  = null;

                        if (!$clockIn && $clockOut) {
                            $remarks = 'Missing clock in';
                        } elseif ($clockIn && !$clockOut) {
                            $remarks = 'Missing clock out';
                        }

                        $totalHours   = $clockIn && $clockOut ? $this->calculateHours($clockIn, $clockOut) : null;
                        $sessionShift = null;
                        if ($clockIn && $employee->department_id) {
                            $clockInCarbon = Carbon::parse($clockIn);
                            $sessionShift  = SessionShift::detectFromClockIn(
                                $employee->department_id,
                                $clockInCarbon->format('H:i:s'),
                                $employee->citizenship === 'WNA',
                                $employee->id,
                                $employee->position,
                                $clockInCarbon->isoWeekday()
                            );
                        }

                        $attendances->push((object)[
                            'employee'      => $employee,
                            'date'          => $currentDate,
                            'clock_in'      => $clockIn  ? Carbon::parse($clockIn)  : null,
                            'clock_out'     => $clockOut ? Carbon::parse($clockOut) : null,
                            'total_hours'   => $totalHours,
                            'status'        => $status,
                            'remarks'       => $remarks,
                            'is_corrected'  => false,
                            'daily_id'      => null,
                            'session_shift' => $sessionShift,
                        ]);
                    } else {
                        // Tidak ada log sama sekali, cek cuti dari cache
                        $leave = ($leavesMap->get($employee->id) ?? collect())
                            ->first(fn($l) => $l->start_date <= $dateStr && $l->end_date >= $dateStr);

                        $status  = $leave ? $attendanceService->mapLeaveTypeToStatus($leave->type) : 'Alpha';
                        $remarks = $leave ? $leave->reason : null;

                        $attendances->push((object)[
                            'employee'      => $employee,
                            'date'          => $currentDate,
                            'clock_in'      => null,
                            'clock_out'     => null,
                            'total_hours'   => null,
                            'status'        => $status,
                            'remarks'       => $remarks,
                            'is_corrected'  => false,
                            'daily_id'      => null,
                            'session_shift' => null,
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

        return view('hr.attendance-logs.index', compact('attendancesPaginated', 'employees', 'departments', 'latestImportSource', 'search', 'departmentId'));
    }

    private function calculateHours($clockIn, $clockOut)
    {
        return Carbon::parse($clockIn)->diffInMinutes(Carbon::parse($clockOut)) / 60;
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

        $employee = Employee::with('department')->findOrFail($employeeId);
        $date = Carbon::parse($date);

        // Untuk status Present/Late, recalculate otomatis berdasarkan clock_in baru.
        // Status cuti/excused/alpha tetap dari form (pilihan manual admin).
        $leaveStatuses = ['Excused', 'Sick Leave', 'Annual Leave', 'Alpha'];
        if (in_array($request->status, $leaveStatuses)) {
            $finalStatus = $request->status;
        } else {
            $attendanceService = app(DailyAttendanceService::class);

            // Deteksi shift berdasarkan clock_in supaya status akurat
            $shift = null;
            if ($request->clock_in && $employee->department_id) {
                $shift = \App\Models\Hr\SessionShift::detectFromClockIn(
                    $employee->department_id,
                    \Carbon\Carbon::createFromFormat('H:i', $request->clock_in)->format('H:i:s'),
                    (bool) $employee->is_wna,
                    $employee->id,
                    $employee->position,
                    $date->isoWeekday()
                );
            }

            $finalStatus = $attendanceService->determineStatus(
                $employee,
                $date,
                $request->clock_in,
                $request->clock_out,
                $shift
            );
        }

        // Recalculate remarks: hapus "Missing clock out/in" jika sudah diisi manual
        $remarks = $request->remarks;
        if ($request->clock_in && $request->clock_out) {
            $remarks = $remarks ?: null; // bersihkan auto-remarks jika sudah lengkap
        }

        $attendance = DailyAttendance::updateOrCreate(
            ['employee_id' => $employeeId, 'date' => $date->format('Y-m-d')],
            [
                'clock_in'   => $request->clock_in,
                'clock_out'  => $request->clock_out,
                'status'     => $finalStatus,
                'remarks'    => $remarks,
                'updated_by' => Auth::id(),
                'is_locked'  => true,
            ]
        );

        app(DailyAttendanceService::class)->calculateAttendanceFields($attendance);

        return redirect()->route('attendance-logs.index', ['date' => $date->format('Y-m-d')])
            ->with('success', 'Attendance data updated successfully.');
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
                    app(DailyAttendanceService::class)->generateForDate(Carbon::parse($date), Auth::id());
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
        app(DailyAttendanceService::class)->generateForDate($date, Auth::id());

        return redirect()->back()->with('success', 'Daily data updated for ' . $date->format('d-m-Y'));
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