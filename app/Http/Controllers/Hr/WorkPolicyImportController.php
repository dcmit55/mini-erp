<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Imports\WorkPoliciesImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class WorkPolicyImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:hr.employees.import');
    }

    /**
     * Proses import data work policy
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Log::info('Starting work policy import', [
                'user_id' => Auth::id(),
                'file_name' => $request->file('file')->getClientOriginalName(),
                'file_size' => $request->file('file')->getSize()
            ]);

            $import = new WorkPoliciesImport();
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();

            $message = "Import selesai!";
            $message .= " Data baru: {$results['success']}";
            $message .= " Data diupdate: {$results['updated']}";
            $message .= " Gagal: {$results['failed']}";

            Log::info('Work policy import completed', $results);

            if ($results['failed'] > 0) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'failed_rows' => $results['failed_rows']
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
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

            Log::warning('Work policy import validation failed', [
                'failed_count' => count($failedRows),
                'failures' => $failedRows
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . count($failedRows) . ' baris memiliki error',
                'failed_rows' => $failedRows
            ], 422);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi file gagal',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Work policy import error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}