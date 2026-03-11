<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Services\FingerspotService;
use App\Services\AttendanceReconcileService;
use App\Models\Hr\Employee;
use App\Models\Hr\AttendanceLog;
use App\Models\FingerprintLog;
use App\Models\Admin\Department;
use App\Exports\DailyAttendanceExport;
use App\Services\DailyAttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;

class FingerspotController extends Controller
{
    protected FingerspotService           $fingerspot;
    protected AttendanceReconcileService  $reconciler;

    public function __construct(FingerspotService $fingerspot, AttendanceReconcileService $reconciler)
    {
        $this->fingerspot  = $fingerspot;
        $this->reconciler  = $reconciler;

        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!in_array(Auth::user()->role, ['super_admin', 'admin_hr', 'admin'])) {
                abort(403);
            }
            return $next($request);
        });
    }

    /**
     * Convert an employee number to the raw PIN used by the fingerprint device.
     *
     * @param string $employeeNo
     * @return string
     */
    private function pinFromEmployeeNo($employeeNo)
    {
        // Remove the "DCM-" prefix (assuming format "DCM-1234")
        $numericPart = substr($employeeNo, 4);
        // Normalize (remove leading zeros) to match the raw PIN from the device
        return $this->reconciler->normalizePin($numericPart);
    }

    // =========================================================================
    // Halaman tampilan
    // =========================================================================

    public function index()
    {
        $defaultDeviceId = config('fingerspot.device_id', '');
        return view('hr.fingerspot.index', compact('defaultDeviceId'));
    }

    public function showSyncForm()
    {
        $defaultDeviceId = config('fingerspot.device_id', '');
        return view('hr.fingerspot.sync', compact('defaultDeviceId'));
    }

    public function showRegisterForm()
    {
        $employees = Employee::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'employee_no', 'name']);
            
        $defaultDeviceId = config('fingerspot.device_id', '');
        return view('hr.fingerspot.register-employee', compact('defaultDeviceId', 'employees'));
    }

    public function showBiometricForm()
    {
        $defaultDeviceId = config('fingerspot.device_id', '');

        $existingPins = FingerprintLog::distinct()->pluck('cloud_id')->filter()->toArray();

        $employees = Employee::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'employee_no', 'name'])
            ->filter(fn($emp) => in_array($this->pinFromEmployeeNo($emp->employee_no), $existingPins))
            ->values();

        return view('hr.fingerspot.register-biometric', compact('defaultDeviceId', 'employees'));
    }

    /**
     * Daftar karyawan yang sudah terdaftar di mesin (pernah scan) – dengan statistik HARIAN (hari ini).
     */
    public function showEmployeeList(Request $request)
    {
        $defaultDeviceId = config('fingerspot.device_id', '');

        // Statistik scan per PIN (cloud_id di fingerprint_logs) — semua waktu
        $fingerprintStats = FingerprintLog::selectRaw('cloud_id, COUNT(*) as total_scans, MAX(event_time) as last_scan')
            ->groupBy('cloud_id')
            ->get()
            ->keyBy('cloud_id');

        $existingPins = $fingerprintStats->keys()->toArray();

        $query = Employee::where('status', 'active')->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_no', 'like', "%{$search}%");
            });
        }

        $filtered = $query->get()
            ->filter(function ($emp) use ($existingPins) {
                return in_array($this->pinFromEmployeeNo($emp->employee_no), $existingPins);
            })
            ->map(function ($emp) use ($fingerprintStats) {
                $pin               = $this->pinFromEmployeeNo($emp->employee_no);
                $stats             = $fingerprintStats->get($pin);
                $emp->device_pin   = $pin;
                $emp->total_scans  = $stats?->total_scans ?? 0;   // jumlah scan hari ini
                $emp->last_scan    = $stats?->last_scan   ?? null; // scan terakhir hari ini
                return $emp;
            })
            ->values();

        $perPage     = 25;
        $currentPage = (int) $request->get('page', 1);
        $employees   = new LengthAwarePaginator(
            $filtered->forPage($currentPage, $perPage),
            $filtered->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('hr.fingerspot.employee-list', compact('defaultDeviceId', 'employees'));
    }

    public function showDeleteForm(Request $request)
    {
        $defaultDeviceId = config('fingerspot.device_id', '');
        $error           = null;

        $existingPins = FingerprintLog::distinct()->pluck('cloud_id')
            ->filter()
            ->toArray();

        $query = Employee::where('status', 'active')->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_no', 'like', "%{$search}%");
            });
        }

        $filtered = $query->get()
            ->filter(fn($emp) => in_array($this->pinFromEmployeeNo($emp->employee_no), $existingPins))
            ->values();

        $perPage     = 50;
        $currentPage = (int) $request->get('page', 1);
        $employees   = new LengthAwarePaginator(
            $filtered->forPage($currentPage, $perPage),
            $filtered->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('hr.fingerspot.delete-employee', compact('defaultDeviceId', 'employees', 'error'));
    }

    public function showDeviceInfoForm()
    {
        $defaultDeviceId = config('fingerspot.device_id', '');
        return view('hr.fingerspot.device-info', compact('defaultDeviceId'));
    }

    public function showTimezoneForm()
    {
        $defaultDeviceId = config('fingerspot.device_id', '');
        return view('hr.fingerspot.set-timezone', compact('defaultDeviceId'));
    }

    public function showRestartForm()
    {
        $defaultDeviceId = config('fingerspot.device_id', '');
        return view('hr.fingerspot.restart', compact('defaultDeviceId'));
    }

    // =========================================================================
    // SYNC ATTENDANCE
    // =========================================================================

    /**
     * Ambil data raw dari mesin fingerspot, simpan ke fingerprint_logs (tanpa duplikat),
     * lalu rekonsiliasi attendance_logs per karyawan per tanggal.
     */
    public function syncAttendance(Request $request)
    {
        $request->validate([
            'device_id'  => 'required|string',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $saved         = 0;
            $duplicates    = 0;
            $invalidFields = 0;
            $notMatched    = 0;
            $affectedPairs = [];

            // Fingerspot API membatasi max 2 hari per request — pecah range menjadi chunk 2 hari
            $current = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);

            while ($current->lte($endDate)) {
                $chunkEnd = $current->copy()->addDay(); // +1 hari = 2 hari per chunk
                if ($chunkEnd->gt($endDate)) {
                    $chunkEnd = $endDate->copy();
                }

                $response = $this->fingerspot->getAttlog(
                    $request->device_id,
                    $current->format('Y-m-d'),
                    $chunkEnd->format('Y-m-d')
                );

                $records = $response['data'] ?? [];

                foreach ($records as $record) {
                    $pinRaw  = (string) ($record['pin'] ?? $record['cloud_id'] ?? null);
                    $timeRaw = $record['scan_date'] ?? $record['time'] ?? $record['scan_time'] ?? null;

                    if (!$pinRaw || !$timeRaw) {
                        Log::warning('Sync: record tidak lengkap', ['record' => $record]);
                        $invalidFields++;
                        continue;
                    }

                    $employee = $this->reconciler->findEmployeeByPin($pinRaw);

                    if (!$employee) {
                        Log::warning('Sync: karyawan tidak ditemukan', [
                            'pin_raw'    => $pinRaw,
                            'pin_lookup' => 'DCM-' . str_pad($this->reconciler->normalizePin($pinRaw), 4, '0', STR_PAD_LEFT),
                        ]);
                        $notMatched++;
                        continue;
                    }

                    $scanCarbon = Carbon::parse($timeRaw);
                    $isSaved    = $this->reconciler->saveRawLog($pinRaw, $scanCarbon, $record);

                    if (!$isSaved) {
                        $duplicates++;
                        continue;
                    }

                    $date = $scanCarbon->format('Y-m-d');
                    $affectedPairs[$employee->id][$date] = true;
                    $saved++;
                }

                $current->addDays(2); // geser ke chunk berikutnya
            }

            if ($saved === 0 && $duplicates === 0 && $notMatched === 0 && $invalidFields === 0) {
                return back()->with('info', 'Tidak ada data absensi pada range tanggal tersebut.');
            }

            // ── Rekonsiliasi & regenerate DailyAttendance ──────────────────────
            $allAffectedDates = [];
            foreach ($affectedPairs as $employeeId => $dates) {
                foreach (array_keys($dates) as $date) {
                    $this->reconciler->reconcile($employeeId, $date);
                    $allAffectedDates[$date] = true;
                }
            }

            foreach (array_keys($allAffectedDates) as $date) {
                app(DailyAttendanceService::class)->generateForDate(Carbon::parse($date));
            }

            $msg = "Sync selesai. {$saved} data baru tersimpan";
            if ($duplicates)    $msg .= ", {$duplicates} duplikat dilewati";
            if ($notMatched)    $msg .= ", {$notMatched} PIN tidak cocok ke karyawan";
            if ($invalidFields) $msg .= ", {$invalidFields} record tidak lengkap (cek log)";

            return back()->with('success', $msg . '.');

        } catch (\Exception $e) {
            Log::error('syncAttendance error', ['message' => $e->getMessage()]);
            return back()->with('error', 'Sync gagal: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // DOWNLOAD ATTENDANCE (XLSX)
    // =========================================================================

    public function showDownloadForm()
    {
        $departments = Department::orderBy('name')->get();
        
        // Ambil karyawan yang sudah terdaftar di device (pernah scan)
        $existingPins = FingerprintLog::distinct()->pluck('cloud_id')
            ->filter()
            ->toArray();
        
        $deviceEmployeeIds = [];
        
        foreach ($existingPins as $pin) {
            $employee = $this->reconciler->findEmployeeByPin($pin);
            if ($employee) {
                $deviceEmployeeIds[] = $employee->id;
            }
        }
        
        $deviceEmployeeIds = array_unique($deviceEmployeeIds);
        
        $deviceEmployees = collect();
        
        if (!empty($deviceEmployeeIds)) {
            $deviceEmployees = Employee::where('status', 'active')
                ->whereIn('id', $deviceEmployeeIds)
                ->orderBy('name')
                ->get(['id', 'employee_no', 'name']);
        }
            
        $defaultDeviceId = config('fingerspot.device_id', '');
        
        return view('hr.fingerspot.download-attendance', compact('departments', 'deviceEmployees', 'defaultDeviceId'));
    }

    public function downloadAttendance(Request $request)
    {
        $request->validate([
            'mode'          => 'required|in:range,month',
            'start_date'    => 'required_if:mode,range|nullable|date',
            'end_date'      => 'required_if:mode,range|nullable|date|after_or_equal:start_date',
            'month'         => 'required_if:mode,month|nullable|integer|min:1|max:12',
            'year'          => 'required_if:mode,month|nullable|integer|min:2020|max:2099',
            'department_id' => 'nullable|integer|exists:departments,id',
            'employee_id'   => 'nullable|integer|exists:employees,id',
        ]);

        if ($request->mode === 'month') {
            $year      = (int) $request->year;
            $month     = (int) $request->month;
            $startDate = now()->setDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
            $endDate   = now()->setDate($year, $month, 1)->endOfMonth()->format('Y-m-d');
            $label     = now()->setDate($year, $month, 1)->format('Y-M');
        } else {
            $startDate = $request->start_date;
            $endDate   = $request->end_date;
            $label     = str_replace('-', '', $startDate) . '_' . str_replace('-', '', $endDate);
        }

        $export   = new DailyAttendanceExport(
            $startDate,
            $endDate,
            $request->department_id ? (int) $request->department_id : null,
            $request->employee_id   ? (int) $request->employee_id   : null
        );

        $filename = "attendance_{$label}.xlsx";

        return Excel::download($export, $filename);
    }

    // =========================================================================
    // Proses form lain
    // =========================================================================

    public function registerEmployee(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'pin'       => 'required|string',
            'privilege' => 'required|in:1,2,3',
            'name'      => 'required|string|max:100',
            'password'  => 'nullable|string',
            'rfid'      => 'nullable|string',
        ]);

        try {
            $this->fingerspot->setUserinfo($request->device_id, [
                'pin'       => $request->pin,
                'name'      => $request->name,
                'privilege' => (int) $request->privilege,
                'password'  => $request->password ?? '',
                'rfid'      => $request->rfid      ?? '',
                'template'  => '',
            ]);
            
            // Simpan relasi antara employee_id, pin, dan username di database jika diperlukan
            
            return back()->with('success', 'Register employee command sent to device.');
        } catch (\Exception $e) {
            return back()->with('error', 'Registration failed: ' . $e->getMessage());
        }
    }

    public function deleteEmployee(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'pin'       => 'required|string',
        ]);

        try {
            $this->fingerspot->deleteUserinfo($request->device_id, $request->pin);
            return back()->with('success', 'Remove employee command sent to device.');
        } catch (\Exception $e) {
            return back()->with('error', 'Remove failed: ' . $e->getMessage());
        }
    }

    public function deviceInfo(Request $request)
    {
        $request->validate(['device_id' => 'required|string']);

        try {
            $info = $this->fingerspot->getDevice($request->device_id);
            return back()->with('device_info', $info)->with('success', 'Device info retrieved successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to get device info: ' . $e->getMessage());
        }
    }

    public function restartDevice(Request $request)
    {
        $request->validate(['device_id' => 'required|string']);

        try {
            $this->fingerspot->restartDevice($request->device_id);
            return back()->with('success', 'Restart command sent to device.');
        } catch (\Exception $e) {
            return back()->with('error', 'Restart failed: ' . $e->getMessage());
        }
    }

    public function setTimezone(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'timezone'  => 'required|string',
        ]);

        try {
            $this->fingerspot->setTime($request->device_id, $request->timezone);
            return back()->with('success', 'Device timezone updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Set timezone failed: ' . $e->getMessage());
        }
    }

    public function registerBiometric(Request $request)
    {
        $request->validate([
            'device_id'    => 'required|string',
            'pin'          => 'required|string',
            'verification' => 'required|integer|in:0,1,2,3,4,5,6,7,8,9,12,13',
        ]);

        try {
            $this->fingerspot->registerOnline($request->device_id, $request->pin, (int) $request->verification);
            return back()->with('success', 'Biometric registration command sent to device.');
        } catch (\Exception $e) {
            return back()->with('error', 'Biometric registration failed: ' . $e->getMessage());
        }
    }
}