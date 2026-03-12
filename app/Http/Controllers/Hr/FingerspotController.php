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
use Illuminate\Support\Facades\Cache;
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
    private function pinFromEmployeeNo($employeeNo): string
    {
        return ltrim(preg_replace('/[^0-9]/', '', $employeeNo), '0') ?: '0';
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

        $scannedPins = FingerprintLog::distinct()->pluck('cloud_id')
            ->map(fn($p) => $this->reconciler->normalizePin($p))
            ->filter()
            ->flip();

        $employees = Employee::where('status', 'active')
            ->orderBy('name')
            ->get()
            ->filter(function ($emp) use ($scannedPins) {
                $pin = $this->pinFromEmployeeNo($emp->employee_no);
                return !is_null($emp->device_registered_at) || isset($scannedPins[$pin]);
            })
            ->map(function ($emp) {
                $emp->device_pin = $this->pinFromEmployeeNo($emp->employee_no);
                return $emp;
            })
            ->values();

        return view('hr.fingerspot.register-biometric', compact('defaultDeviceId', 'employees'));
    }

    /**
     * Parse berbagai format response getAllPin dari Fingerspot API ke array PIN ter-normalisasi.
     */
    private function parseAllPinResponse(array $apiResult): array
    {
        $raw = $apiResult['data'] ?? $apiResult['pin'] ?? $apiResult['pins'] ?? null;

        if (is_null($raw)) {
            return [];
        }

        // Format string: "1,2,3" atau "1 2 3" atau "1\t2\t3" atau "1\n2\n3"
        if (is_string($raw)) {
            $pins = preg_split('/[\s,;|]+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY);
            return collect($pins)
                ->map(fn($p) => $this->reconciler->normalizePin(trim($p)))
                ->filter()
                ->values()
                ->toArray();
        }

        if (is_array($raw)) {
            return collect($raw)
                ->map(function ($item) {
                    if (is_array($item)) {
                        // { "pin": "1", "name": "..." } atau { "no": "1" }
                        $pin = $item['pin'] ?? $item['no'] ?? $item['id'] ?? null;
                    } elseif (is_string($item) || is_numeric($item)) {
                        $pin = (string) $item;
                    } else {
                        $pin = null;
                    }
                    return $pin ? $this->reconciler->normalizePin(trim($pin)) : null;
                })
                ->filter()
                ->values()
                ->toArray();
        }

        return [];
    }

    /**
     * Kumpulkan PIN yang terdaftar di device — hanya dari device_registered_at.
     * Scan history (fingerprint_logs) tidak dipakai sebagai penanda registrasi.
     */
    private function getRegisteredPins(): array
    {
        return Employee::whereNotNull('device_registered_at')
            ->pluck('employee_no')
            ->map(fn($no) => $this->pinFromEmployeeNo($no))
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Daftar semua karyawan aktif dengan status device dan statistik scan.
     * on_device = device_registered_at IS NOT NULL  OR  pernah scan di fingerprint_logs.
     */
    public function showEmployeeList(Request $request)
    {
        $defaultDeviceId = config('fingerspot.device_id', '');

        // Statistik scan per PIN dari fingerprint_logs (keyed by normalized PIN)
        $fingerprintStats = FingerprintLog::selectRaw('cloud_id, COUNT(*) as total_scans, MAX(event_time) as last_scan')
            ->groupBy('cloud_id')
            ->get()
            ->keyBy(fn($row) => $this->reconciler->normalizePin($row->cloud_id));

        $mapEmployee = function ($emp) use ($fingerprintStats) {
            $pin   = $this->pinFromEmployeeNo($emp->employee_no);
            $stats = $fingerprintStats->get($pin);

            $emp->device_pin           = $pin;
            $emp->on_device            = !is_null($emp->device_registered_at) || $fingerprintStats->has($pin);
            $emp->total_scans          = $stats?->total_scans ?? 0;
            $emp->last_scan            = $stats?->last_scan   ?? null;
            $emp->biometric_registered = ($emp->total_scans > 0);
            return $emp;
        };

        // Semua employee aktif — untuk hitung statistik (tidak terpengaruh search)
        $allActive = Employee::where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map($mapEmployee);

        $totalActive      = $allActive->count();
        $totalOnDevice    = $allActive->where('on_device', true)->count();
        $totalNoBiometric = $allActive->filter(fn($e) => $e->on_device && !$e->biometric_registered)->count();

        // Query untuk tabel — bisa kena search + filter
        $query = Employee::where('status', 'active')->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_no', 'like', "%{$search}%");
            });
        }

        $tableData = $query->get()->map($mapEmployee);

        // Apply dropdown filter
        $filtered = match($request->input('filter')) {
            'on_device'      => $tableData->filter(fn($e) => $e->on_device),
            'not_registered' => $tableData->filter(fn($e) => !$e->on_device),
            'no_biometric'   => $tableData->filter(fn($e) => $e->on_device && !$e->biometric_registered),
            default          => $tableData,
        };
        $filtered = $filtered->values();

        $perPage     = 25;
        $currentPage = (int) $request->get('page', 1);
        $employees   = new LengthAwarePaginator(
            $filtered->forPage($currentPage, $perPage),
            $filtered->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('hr.fingerspot.employee-list', compact(
            'defaultDeviceId', 'employees', 'totalActive', 'totalOnDevice', 'totalNoBiometric'
        ));
    }

    /**
     * Kirim perintah get_all_pin ke mesin (async).
     * Mesin akan mengirim balik data PIN via webhook ke /api/fingerspot/webhook/get_all_pin.
     */
    public function syncFromDevice(Request $request)
    {
        $deviceId = config('fingerspot.device_id');
        if (!$deviceId) {
            return back()->with('error', 'Device ID tidak dikonfigurasi.');
        }

        $transId = uniqid('sync_', true);
        Cache::put('sync_' . $transId, ['status' => 'pending', 'started_at' => now()->toIso8601String()], now()->addMinutes(10));

        try {
            $response = $this->fingerspot->getAllPin($deviceId, $transId);

            if ($response['success'] ?? false) {
                return back()->with('info', 'Perintah sync dikirim ke device. Data akan diproses setelah device merespons. Silakan refresh halaman beberapa saat lagi.');
            }

            Cache::forget('sync_' . $transId);
            $msg = $response['msg'] ?? $response['message'] ?? 'Gagal mengirim perintah ke device.';
            return back()->with('error', $msg);

        } catch (\Exception $e) {
            Cache::forget('sync_' . $transId);
            return back()->with('error', 'Gagal menghubungi device: ' . $e->getMessage());
        }
    }

    public function showDeleteForm(Request $request)
    {
        $defaultDeviceId = config('fingerspot.device_id', '');

        // PIN yang pernah scan (dari fingerprint_logs)
        $scannedPins = FingerprintLog::distinct()->pluck('cloud_id')
            ->map(fn($p) => $this->reconciler->normalizePin($p))
            ->filter()
            ->flip(); // jadikan key untuk lookup O(1)

        $query = Employee::where('status', 'active')->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_no', 'like', "%{$search}%");
            });
        }

        $filtered = $query->get()
            ->filter(function ($emp) use ($scannedPins) {
                $pin = $this->pinFromEmployeeNo($emp->employee_no);
                // on_device: device_registered_at terisi ATAU pernah scan
                return !is_null($emp->device_registered_at) || isset($scannedPins[$pin]);
            })
            ->map(function ($emp) {
                $emp->device_pin = $this->pinFromEmployeeNo($emp->employee_no);
                return $emp;
            })
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

        return view('hr.fingerspot.delete-employee', compact('defaultDeviceId', 'employees'));
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
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        // Jika tanggal tidak diisi, sync semua data dari awal device hingga hari ini
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::parse(config('fingerspot.device_start_date', '2026-03-07'));

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::today();

        try {
            $saved         = 0;
            $duplicates    = 0;
            $invalidFields = 0;
            $notMatched    = 0;
            $affectedPairs = [];

            // Fingerspot API membatasi max 2 hari per request — pecah range menjadi chunk 2 hari
            $current = $startDate->copy();

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

            // Tandai employee sebagai terdaftar di device
            $employee = $this->reconciler->findEmployeeByPin($request->pin);
            if ($employee) {
                $employee->update(['device_registered_at' => now()]);
            }

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

            // Hapus tanda registrasi di database
            $employee = $this->reconciler->findEmployeeByPin($request->pin);
            if ($employee) {
                $employee->update(['device_registered_at' => null]);
            }

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