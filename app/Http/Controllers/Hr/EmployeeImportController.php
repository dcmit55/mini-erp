<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Imports\EmployeesImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class EmployeeImportController extends Controller
{
    /**
     * Constructor dengan middleware
     */
    public function __construct()
    {
        $this->middleware('auth');
        
        // Hanya user dengan role tertentu yang bisa akses
        $this->middleware(function ($request, $next) {
            $rolesAllowed = ['super_admin', 'admin_hr', 'admin'];
            if (!in_array(Auth::user()->role, $rolesAllowed)) {
                abort(403, 'Unauthorized access to import employees.');
            }
            return $next($request);
        });
    }

    /**
     * Proses import data
     */
    public function import(Request $request)
    {
        // Validasi file
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // Max 10MB
        ]);

        try {
            // Log awal import
            Log::info('Starting employee import', [
                'user_id' => Auth::id(),
                'file_name' => $request->file('file')->getClientOriginalName(),
                'file_size' => $request->file('file')->getSize()
            ]);

            // Buat instance import
            $import = new EmployeesImport();
            
            // Proses import
            Excel::import($import, $request->file('file'));

            // Dapatkan hasil
            $results = $import->getResults();

            // Buat pesan
            $message = "Import selesai!";
            $message .= " Data baru: {$results['success']}";
            $message .= " Data diupdate: {$results['updated']}";
            $message .= " Gagal: {$results['failed']}";

            // Log hasil
            Log::info('Import completed', $results);

            // Jika ada data yang gagal, kirim detailnya
            if ($results['failed'] > 0) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'failed_rows' => $results['failed_rows']
                ], 422);
            }

            // Sukses semua
            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // Error validasi Excel
            $failures = $e->failures();
            
            $failedRows = [];
            foreach ($failures as $failure) {
                $failedRows[] = [
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'name' => $failure->values()['name'] ?? 'Unknown',
                    'error' => implode(', ', $failure->errors())
                ];
            }

            Log::warning('Import validation failed', [
                'failed_count' => count($failedRows),
                'failures' => $failedRows
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . count($failedRows) . ' baris memiliki error',
                'failed_rows' => $failedRows
            ], 422);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Error validasi request
            return response()->json([
                'success' => false,
                'message' => 'Validasi file gagal',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            // Error umum
            Log::error('Import error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}