<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hr\AttendanceLog;
use App\Models\Hr\Employee;
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
     * Menampilkan daftar attendance logs dengan filter
     */
    public function index(Request $request)
    {
        $query = AttendanceLog::with('employee')
            ->activeEmployees()
            ->latest('created_at'); // Urutkan berdasarkan waktu import (terbaru di atas)

        // Filter rentang tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif (!$request->has('all')) {
            // Default: 30 hari terakhir (kecuali ada parameter 'all')
            $query->where('date', '>=', Carbon::today()->subDays(30));
        }

        // Filter employee
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $logs = $query->paginate(50)->withQueryString();

        // Untuk dropdown filter employee
        $employees = Employee::where('status', 'active')->orderBy('name')->get(['id', 'name', 'employee_no']);

        // Ambil informasi import terakhir untuk ditampilkan di view
        $latestImportSource = null;
        if (!$request->has('all') && !$request->filled('start_date') && !$request->filled('end_date')) {
            $latest = AttendanceLog::latest('created_at')->first();
            if ($latest) {
                $latestImportSource = $latest->import_source;
            }
        }

        return view('hr.attendance-logs.index', compact('logs', 'employees', 'latestImportSource'));
    }

    /**
     * Proses import file Excel (AJAX) dengan dukungan konversi otomatis .xls ke .xlsx
     */
    public function storeImport(Request $request)
    {
        // Validasi file dengan aturan yang lebih fleksibel
        $request->validate([
            'file' => 'required|file|mimetypes:application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/csv,text/csv,text/plain,application/octet-stream|max:10240',
        ]);

        $file = $request->file('file');
        $originalExtension = $file->getClientOriginalExtension();
        $tempFile = null;
        $importFile = $file->getPathname();
        $importSource = $file->getClientOriginalName();

        try {
            // Jika file adalah .xls, coba konversi ke .xlsx sementara
            if (strtolower($originalExtension) === 'xls') {
                try {
                    Log::info('Attempting to convert XLS file: ' . $file->getClientOriginalName());

                    // Load file menggunakan PhpSpreadsheet
                    $spreadsheet = IOFactory::load($file->getPathname());

                    // Buat temporary file .xlsx
                    $tempPath = tempnam(sys_get_temp_dir(), 'converted_') . '.xlsx';
                    $writer = new Xlsx($spreadsheet);
                    $writer->save($tempPath);

                    // Gunakan file temporary untuk import
                    $tempFile = $tempPath;
                    $importFile = $tempPath;
                    $importSource = $file->getClientOriginalName() . ' (converted to xlsx)';

                    Log::info('Conversion successful: ' . $tempPath);
                } catch (\Exception $e) {
                    Log::error('Conversion failed: ' . $e->getMessage());

                    // Hapus temporary file jika sudah dibuat
                    if ($tempFile && file_exists($tempFile)) {
                        unlink($tempFile);
                        $tempFile = null;
                    }

                    // Beri pesan error yang jelas
                    return response()->json([
                        'success' => false,
                        'message' => 'File .xls tidak dapat dibaca. Silakan buka file dengan Microsoft Excel, simpan ulang sebagai format .xlsx, lalu coba lagi.'
                    ], 422);
                }
            }

            $import = new AttendancesImport($importSource);

            // Lakukan import menggunakan file (asli atau hasil konversi)
            Excel::import($import, $importFile);

            $success = $import->getSuccessCount();
            $failed = $import->getFailedRows();

            // Hapus temporary file jika ada
            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }

            if (count($failed) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Import selesai dengan error. Berhasil: {$success}, Gagal: " . count($failed),
                    'failed_rows' => $failed
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => "Semua {$success} data berhasil diimport."
            ]);

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // Tangani error validasi dari package Excel
            $failures = $e->failures();
            $errorMessages = [];
            foreach ($failures as $failure) {
                $errorMessages[] = "Baris " . $failure->row() . ": " . implode(', ', $failure->errors());
            }

            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }

            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode('; ', $errorMessages)
            ], 422);

        } catch (\Exception $e) {
            // Hapus temporary file jika ada
            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }

            Log::error('Import error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Import gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export data attendance logs berdasarkan filter
     */
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
            return redirect()->back()->with('warning', 'Tidak ada data untuk diekspor.');
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