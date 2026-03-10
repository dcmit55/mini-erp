<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Services\FingerspotService;
use App\Models\Hr\Employee;
use App\Models\Hr\AttendanceLog;
use App\Models\FingerprintLog;
use App\Services\DailyAttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FingerspotController extends Controller
{
    protected FingerspotService $fingerspot;

    public function __construct(FingerspotService $fingerspot)
    {
        $this->fingerspot = $fingerspot;

        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!in_array(Auth::user()->role, ['super_admin', 'admin_hr', 'admin'])) {
                abort(403);
            }
            return $next($request);
        });
    }

    // ─── Halaman utama ───────────────────────────────────────────────────────
    public function index()
    {
        $defaultDeviceId = config('fingerspot.device_id', '');
        return view('hr.fingerspot.index', compact('defaultDeviceId'));
    }

    // ─── Halaman form untuk setiap fitur ─────────────────────────────────────

    public function showSyncForm()
    {
        $defaultDeviceId = config('fingerspot.device_id', '');
        return view('hr.fingerspot.sync', compact('defaultDeviceId'));
    }

    public function showRegisterForm()
    {
        $defaultDeviceId = config('fingerspot.device_id', '');
        return view('hr.fingerspot.register-employee', compact('defaultDeviceId'));
    }

    public function showBiometricForm()
    {
        $defaultDeviceId = config('fingerspot.device_id', '');
        return view('hr.fingerspot.register-biometric', compact('defaultDeviceId'));
    }

    /**
     * Tampilkan daftar karyawan yang sudah terdaftar di mesin (pernah scan)
     */
    public function showEmployeeList(Request $request)
    {
        $defaultDeviceId = config('fingerspot.device_id', '');

        // Hitung total scan dan last scan per employee machine ID
        $fingerprintStats = FingerprintLog::selectRaw('cloud_id, COUNT(*) as total_scans, MAX(event_time) as last_scan')
            ->groupBy('cloud_id')
            ->get()
            ->keyBy(fn($r) => ltrim((string) $r->cloud_id, '0'));

        $existingPins = $fingerprintStats->keys()->toArray();

        // Query karyawan aktif + optional search
        $query = Employee::where('status', 'active')->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_no', 'like', "%{$search}%");
            });
        }

        // Filter hanya yang PIN-nya ada di fingerprint_logs, lalu attach stats
        $filtered = $query->get()
            ->filter(function ($emp) use ($existingPins) {
                $pin = ltrim(preg_replace('/[^0-9]/', '', $emp->employee_no), '0');
                return in_array($pin, $existingPins);
            })
            ->map(function ($emp) use ($fingerprintStats) {
                $pin   = ltrim(preg_replace('/[^0-9]/', '', $emp->employee_no), '0');
                $stats = $fingerprintStats->get($pin);
                $emp->device_pin   = $pin;
                $emp->total_scans  = $stats ? $stats->total_scans : 0;
                $emp->last_scan    = $stats ? $stats->last_scan   : null;
                return $emp;
            })
            ->values();

        // Manual pagination
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

    /**
     * Tampilkan halaman hapus karyawan dari mesin
     */
    public function showDeleteForm(Request $request)
    {
        $defaultDeviceId = config('fingerspot.device_id', '');
        $error = null;

        // Ambil semua employee machine ID yang pernah scan, strip leading zeros
        $existingMachineIds = FingerprintLog::distinct()->pluck('cloud_id')
            ->map(fn($id) => ltrim((string) $id, '0'))
            ->filter()
            ->toArray();

        // Query karyawan aktif dengan optional search
        $query = Employee::where('status', 'active')->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_no', 'like', "%{$search}%");
            });
        }

        // Filter hanya karyawan yang machine ID-nya ada di fingerprint_logs
        $filtered = $query->get()->filter(function ($emp) use ($existingMachineIds) {
            $pin = ltrim(preg_replace('/[^0-9]/', '', $emp->employee_no), '0');
            return in_array($pin, $existingMachineIds);
        })->values();

        // Buat paginator manual dari koleksi yang sudah difilter
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

    // ─── Proses form ──────────────────────────────────────────────────────────
    public function syncAttendance(Request $request)
    {
        $request->validate([
            'device_id'  => 'required|string',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $response = $this->fingerspot->getAttlog(
                $request->device_id,
                $request->start_date,
                $request->end_date
            );

            // Jika Fingerspot mengembalikan data langsung di response (bukan via webhook push)
            $records = $response['data'] ?? [];

            if (empty($records)) {
                return back()->with('info', 'Sync command sent. No attendance data returned directly — data may arrive via webhook push.');
            }

            $saved   = 0;
            $skipped = 0;
            $dates   = [];

            foreach ($records as $record) {
                // Format field: pin / cloud_id = employee ID on device, time / scan_time = timestamp
                $pin      = (string) ($record['pin'] ?? $record['cloud_id'] ?? null);
                $timeRaw  = $record['time'] ?? $record['scan_time'] ?? null;

                if (!$pin || !$timeRaw) {
                    $skipped++;
                    continue;
                }

                // Cocokkan ke employee
                $employee = Employee::where('employee_no', $pin)
                    ->orWhere('employee_no', 'DCM-' . str_pad($pin, 4, '0', STR_PAD_LEFT))
                    ->orWhere('employee_no', 'like', '%-' . $pin)
                    ->first();

                if (!$employee) {
                    Log::warning('Sync: employee not found', ['pin' => $pin]);
                    $skipped++;
                    continue;
                }

                $scanCarbon = Carbon::parse($timeRaw);
                $date       = $scanCarbon->format('Y-m-d');
                $timeOnly   = $scanCarbon->format('H:i:s');

                // Simpan ke fingerprint_logs
                FingerprintLog::create([
                    'cloud_id'   => $pin,
                    'event_time' => $scanCarbon,
                    'payload'    => $record,
                ]);

                // Update attendance_logs
                $log = AttendanceLog::where('employee_id', $employee->id)
                    ->whereDate('date', $date)
                    ->first();

                if (!$log) {
                    AttendanceLog::create([
                        'employee_id'   => $employee->id,
                        'date'          => $date,
                        'clock_in'      => $timeOnly,
                        'import_source' => 'fingerprint_sync',
                    ]);
                } elseif (is_null($log->clock_out)) {
                    $log->clock_out = $timeOnly;
                    $log->save();
                }

                $dates[] = $date;
                $saved++;
            }

            // Regenerate daily attendances untuk semua tanggal yang terdampak
            foreach (array_unique($dates) as $date) {
                app(DailyAttendanceService::class)->generateForDate(Carbon::parse($date));
            }

            return back()->with('success', "Sync complete. {$saved} record(s) saved" . ($skipped ? ", {$skipped} skipped." : '.'));

        } catch (\Exception $e) {
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    public function registerEmployee(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'pin'       => 'required|string',
            'name'      => 'required|string|max:100',
            'privilege' => 'required|in:0,1,2,3',
        ]);

        try {
            $this->fingerspot->setUserinfo($request->device_id, [
                'pin'       => $request->pin,
                'name'      => $request->name,
                'privilege' => $request->privilege,
                'password'  => $request->password ?? '',
                'rfid'      => $request->rfid      ?? '',
            ]);
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
