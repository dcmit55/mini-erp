<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WebhookController extends Controller
{
    /**
     * Handle incoming webhook from fingerprint device
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        // Log request masuk
        Log::info('Fingerprint webhook received', [
            'ip' => $request->ip(),
            'content_type' => $request->header('Content-Type'),
            'user_agent' => $request->userAgent()
        ]);

        // Validasi input
        $validator = Validator::make($request->all(), [
            'device_id' => 'sometimes|string|max:50',
            'event_type' => 'sometimes|string|in:checkin,checkout,verify,test',
            'timestamp' => 'sometimes|date',
            'fingerprint_data' => 'sometimes|array',
            'fingerprint_data.user_id' => 'sometimes|string',
            'fingerprint_data.template' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            Log::warning('Webhook validation failed', [
                'errors' => $validator->errors()->toArray()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid data format',
                'errors' => $validator->errors()
            ], 422);
        }

        // Ambil data yang sudah divalidasi
        $validatedData = $validator->validated();
        
        // TODO: Proses data fingerprint di sini
        // Contoh: simpan ke database, kirim notifikasi, dll
        
        // Contoh response sukses
        return response()->json([
            'success' => true,
            'message' => 'Webhook processed successfully',
            'received_at' => now()->toIso8601String(),
            'data' => $validatedData
        ], 200);
    }
}