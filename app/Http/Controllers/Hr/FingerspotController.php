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
        $this->middleware('can:hr.fingerspot.view');
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
            ->with('department')
            ->orderBy('name')
            ->get()
            ->map(function ($emp) {
                $emp->device_pin = $this->pinFromEmployeeNo($emp->employee_no);
                return $emp;
            });

        $departments     = \App\Models\Admin\Department::orderBy('name')->get(['id', 'name']);
        $defaultDeviceId = config('fingerspot.device_id', '');

        return view('hr.fingerspot.register-employee', compact('defaultDeviceId', 'employees', 'departments'));
    }

    public function showBiometricForm()
    {
        $defaultDeviceId = config('fingerspot.device_id', '');

        $employees = Employee::where('status', 'active')
            ->whereNotNull('device_registered_at')
            ->orderBy('name')
            ->get()
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

        // Statistik scan per PIN — di-cache 5 menit agar tidak query besar tiap page load
        $fingerprintStats = Cache::remember('fingerspot_stats', 300, function () {
            return FingerprintLog::selectRaw('cloud_id, COUNT(*) as total_scans, MAX(event_time) as last_scan')
                ->groupBy('cloud_id')
                ->get()
                ->keyBy(fn($row) => $this->reconciler->normalizePin($row->cloud_id));
        });

        $mapEmployee = function ($emp) use ($fingerprintStats) {
            $pin   = $this->pinFromEmployeeNo($emp->employee_no);
            $stats = $fingerprintStats->get($pin);

            $emp->device_pin           = $pin;
            $emp->on_device            = !is_null($emp->device_registered_at);
            $emp->total_scans          = $stats?->total_scans ?? 0;
            $emp->last_scan            = $stats?->last_scan   ?? null;
            // biometric_registered: true jika sudah pernah scan (ada di fingerprint_logs)
            // ATAU sudah pernah tercatat biometric_enrolled_at.
            // Fingerspot API tidak menyediakan endpoint cek status biometric secara langsung.
            $emp->biometric_registered = !is_null($emp->biometric_enrolled_at) || ($emp->total_scans > 0);
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
     * Kirim perintah get_all_pin ke mesin, lalu rekonsiliasi registrasi device ↔ sistem.
     *
     * Beberapa firmware Fingerspot mengembalikan daftar PIN langsung di response body
     * (synchronous). Jika PIN sudah ada di response, langsung diproses tanpa menunggu
     * webhook. Jika tidak, perintah tetap dikirim dan webhook akan memproses saat device
     * merespons (async).
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

            // Coba proses PIN langsung dari response (sync response)
            $pins = $this->parseAllPinResponse($response);

            if (!empty($pins)) {
                Cache::forget('sync_' . $transId);
                [$marked, $cleared] = $this->reconcileDevicePins($pins);

                return back()->with('success',
                    "Sync selesai. {$marked} karyawan ditandai terdaftar, {$cleared} karyawan dihapus dari daftar device."
                );
            }

            // Tidak ada PIN di response → async (device akan callback via webhook)
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

    /**
     * Rekonsiliasi daftar PIN dari device dengan database.
     * Mengembalikan [jumlah_marked, jumlah_cleared].
     */
    private function reconcileDevicePins(array $normalizedPins): array
    {
        $deviceEmployeeNos = collect($normalizedPins)
            ->map(fn($pin) => 'DCM-' . str_pad($pin, 4, '0', STR_PAD_LEFT))
            ->unique()
            ->values()
            ->toArray();

        // Tandai yang ada di mesin tapi belum tercatat di sistem
        $marked = Employee::whereIn('employee_no', $deviceEmployeeNos)
            ->whereNull('device_registered_at')
            ->get();
        foreach ($marked as $emp) {
            $emp->update([
                'device_registered_at'  => now(),
                'biometric_enrolled_at' => now(),
            ]);
        }

        // Hapus tanda untuk yang sudah tidak ada di mesin
        $cleared = Employee::whereNotNull('device_registered_at')
            ->whereNotIn('employee_no', $deviceEmployeeNos)
            ->get();
        foreach ($cleared as $emp) {
            $emp->update(['device_registered_at' => null, 'biometric_enrolled_at' => null]);
        }

        Log::info('reconcileDevicePins', [
            'device_pins'  => count($deviceEmployeeNos),
            'marked'       => $marked->count(),
            'cleared'      => $cleared->count(),
            'cleared_nos'  => $cleared->pluck('employee_no')->toArray(),
        ]);

        return [$marked->count(), $cleared->count()];
    }

    public function showBulkRegisterForm(Request $request)
    {
        $defaultDeviceId = config('fingerspot.device_id', '');

        $departments = \App\Models\Admin\Department::orderBy('name')->get(['id', 'name']);

        $query = Employee::where('status', 'active')
            ->with('department')
            ->orderBy('name');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")->orWhere('employee_no', 'like', "%{$s}%"));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->input('filter') === 'not_registered') {
            $query->whereNull('device_registered_at');
        } elseif ($request->input('filter') === 'registered') {
            $query->whereNotNull('device_registered_at');
        }

        $employees = $query->get()->map(function ($emp) {
            $emp->device_pin = $this->pinFromEmployeeNo($emp->employee_no);
            return $emp;
        });

        return view('hr.fingerspot.bulk-register', compact('defaultDeviceId', 'employees', 'departments'));
    }

    public function bulkRegisterEmployees(Request $request)
    {
        $request->validate([
            'employee_ids'   => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:employees,id',
            'privilege'      => 'required|in:1,2,3',
        ]);

        $deviceId  = config('fingerspot.device_id');
        if (!$deviceId) {
            return back()->with('error', 'Device ID tidak dikonfigurasi.');
        }

        $employees = Employee::whereIn('id', $request->employee_ids)->get();
        $success   = 0;
        $failed    = [];

        foreach ($employees as $emp) {
            $pin = $this->pinFromEmployeeNo($emp->employee_no);
            try {
                $this->fingerspot->setUserinfo($deviceId, [
                    'pin'       => $pin,
                    'name'      => $emp->name,
                    'privilege' => (int) $request->privilege,
                    'password'  => '',
                    'rfid'      => '',
                    'template'  => '',
                ]);
                $emp->update(['device_registered_at' => now()]);
                $success++;
            } catch (\Exception $e) {
                $failed[] = $emp->name;
                Log::warning("bulkRegister: gagal register {$emp->name} (PIN {$pin}): " . $e->getMessage());
            }
        }

        Cache::forget('fingerspot_stats');

        $msg = "{$success} karyawan berhasil didaftarkan ke device.";
        if (!empty($failed)) {
            $msg .= ' Gagal: ' . implode(', ', $failed) . '.';
            return back()->with('warning', $msg);
        }

        return back()->with('success', $msg);
    }

    public function showDeleteForm(Request $request)
    {
        $defaultDeviceId = config('fingerspot.device_id', '');

        $query = Employee::where('status', 'active')->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_no', 'like', "%{$search}%");
            });
        }

        $filtered = $query->get()
            ->filter(fn($emp) => !is_null($emp->device_registered_at))
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

        $deviceId = $request->device_id;

        // Jika tanggal tidak diisi, sync semua data dari awal device hingga hari ini
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::parse(config('fingerspot.device_start_date', '2026-03-07'));

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::today();

        // Terapkan batas minimum tanggal (FINGERSPOT_SYNC_VALID_FROM di .env).
        // Berguna ketika data lama di cloud Fingerspot tidak bisa dihapus via API
        // dan kita ingin mengabaikannya setelah reset database.
        $syncValidFrom = config('fingerspot.sync_valid_from');
        if ($syncValidFrom) {
            $validFrom = Carbon::parse($syncValidFrom);
            if ($startDate->lt($validFrom)) {
                $startDate = $validFrom;
            }
        }

        try {
            $saved         = 0;
            $duplicates    = 0;
            $invalidFields = 0;
            $notMatched    = 0;
            $affectedPairs = [];

            // ── 1) Pull attendance data ──────────────────────────────────────
            $current = $startDate->copy();

            while ($current->lte($endDate)) {
                $chunkEnd = $current->copy()->addDay();
                if ($chunkEnd->gt($endDate)) {
                    $chunkEnd = $endDate->copy();
                }

                $response = $this->fingerspot->getAttlog(
                    $deviceId,
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

                    // Hanya set field registrasi untuk scan BARU (bukan historis/duplikat)
                    $updates = [];
                    if (is_null($employee->device_registered_at)) {
                        $updates['device_registered_at'] = now();
                    }
                    if (is_null($employee->biometric_enrolled_at)) {
                        $updates['biometric_enrolled_at'] = now();
                    }
                    if (!empty($updates)) {
                        $employee->update($updates);
                    }

                    $date = $scanCarbon->format('Y-m-d');
                    $affectedPairs[$employee->id][$date] = true;
                    $saved++;
                }

                $current->addDays(2);
            }

            // ── 2) Rekonsiliasi & regenerate DailyAttendance ─────────────────
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

            // ── 3) Sync registrasi via getAllPin ─────────────────────────────
            // Coba ambil daftar PIN yang terdaftar di mesin saat ini.
            // Jika device mengembalikan data langsung → reconcile penuh (tambah + hapus).
            // Jika async (hanya success:true tanpa data) → webhook akan handle nanti.
            $regMarked  = 0;
            $regCleared = 0;

            try {
                // Buat transId dan simpan ke cache agar webhook callback bisa dikenali
                $pinTransId = uniqid('sync_', true);
                Cache::put('sync_' . $pinTransId, ['status' => 'pending', 'started_at' => now()->toIso8601String()], now()->addMinutes(10));

                $allPinResponse = $this->fingerspot->getAllPin($deviceId, $pinTransId);

                $devicePins = $this->parseAllPinResponse($allPinResponse);

                if (!empty($devicePins)) {
                    // Device langsung kirim data (sync response)
                    Cache::forget('sync_' . $pinTransId);
                    [$regMarked, $regCleared] = $this->reconcileDevicePins($devicePins);
                    Log::info('getAllPin sync response: reconcile selesai', ['marked' => $regMarked, 'cleared' => $regCleared]);
                } else {
                    // Device akan kirim data via webhook — cache entry sudah siap
                    Log::info('getAllPin: menunggu webhook callback', ['trans_id' => $pinTransId]);
                }
            } catch (\Exception $e) {
                Log::warning('Sync: getAllPin gagal: ' . $e->getMessage());
            }

            Cache::forget('fingerspot_stats');

            // ── 4) Hasil ─────────────────────────────────────────────────────
            $msg = "Sync selesai. {$saved} data baru tersimpan";
            if ($duplicates)    $msg .= ", {$duplicates} duplikat dilewati";
            if ($notMatched)    $msg .= ", {$notMatched} PIN tidak cocok ke karyawan";
            if ($invalidFields) $msg .= ", {$invalidFields} record tidak lengkap";
            if ($regMarked)     $msg .= ". {$regMarked} karyawan baru terdeteksi di mesin";
            if ($regCleared)    $msg .= ". {$regCleared} karyawan dihapus dari daftar (tidak ada di mesin)";

            if ($saved === 0 && $duplicates === 0 && $notMatched === 0 && $regMarked === 0 && $regCleared === 0) {
                return back()->with('info', 'Tidak ada perubahan data.');
            }

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

    /**
     * Reset device_registered_at (dan biometric_enrolled_at) di database saja,
     * tanpa memanggil API mesin. Berguna saat mesin direset/dikosongkan dan
     * data di database tidak sinkron dengan kondisi mesin.
     */
    public function resetDeviceStatus(Request $request)
    {
        $request->validate([
            'employee_id'   => 'nullable|integer|exists:employees,id',
            'mark_enrolled' => 'nullable|boolean',
        ]);

        $markEnrolled = $request->boolean('mark_enrolled');

        if ($request->filled('employee_id')) {
            $employee = Employee::findOrFail($request->employee_id);

            if ($markEnrolled) {
                // Tandai manual sebagai sudah terdaftar di mesin + punya biometric
                $employee->update([
                    'device_registered_at' => $employee->device_registered_at ?? now(),
                    'biometric_enrolled_at' => $employee->biometric_enrolled_at ?? now(),
                ]);
                Cache::forget('fingerspot_stats');
                return back()->with('success', "{$employee->name} ditandai sudah terdaftar di mesin.");
            }

            // Reset / unregister dari sisi DB
            $employee->update(['device_registered_at' => null, 'biometric_enrolled_at' => null]);
            Cache::forget('fingerspot_stats');
            return back()->with('success', "Status device {$employee->name} berhasil direset.");
        }

        // Reset semua karyawan
        Employee::whereNotNull('device_registered_at')
            ->update(['device_registered_at' => null, 'biometric_enrolled_at' => null]);
        Cache::forget('fingerspot_stats');

        return back()->with('success', 'Status device semua karyawan berhasil direset.');
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