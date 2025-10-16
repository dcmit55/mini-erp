<?php
// filepath: c:\xampp\htdocs\inventory-system-v2-upg-larv-oct\app\Services\LarkIntegration.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LarkIntegration
{
    protected $appId;
    protected $appSecret;
    protected $baseId;
    protected $tableId;

    public function __construct()
    {
        $this->appId = config('services.lark.app_id');
        $this->appSecret = config('services.lark.app_secret');
        $this->baseId = config('services.lark.base_id');
        $this->tableId = config('services.lark.table_id');
    }

    public function getAccessToken()
    {
        try {
            Log::info('Requesting tenant access token...');

            $response = Http::timeout(30)->post('https://open.larksuite.com/open-apis/auth/v3/tenant_access_token/internal', [
                'app_id' => $this->appId,
                'app_secret' => $this->appSecret,
            ]);

            Log::info('Token response status: ' . $response->status());
            Log::info('Token response body: ' . $response->body());

            if (!$response->successful()) {
                Log::error('Failed to get access token: ' . $response->body());
                return null;
            }

            $data = $response->json();

            if (isset($data['code']) && $data['code'] !== 0) {
                Log::error('Lark API error: ' . ($data['msg'] ?? 'Unknown error'));
                return null;
            }

            $token = $data['tenant_access_token'] ?? null;
            Log::info('Token obtained: ' . ($token ? 'Yes' : 'No'));

            return $token;
        } catch (\Exception $e) {
            Log::error('Exception getting access token: ' . $e->getMessage());
            return null;
        }
    }

    public function fetchJobOrders($pageSize = 100)
    {
        $token = $this->getAccessToken();

        if (!$token) {
            Log::error('Failed to get Lark access token');
            return null;
        }

        try {
            $allRecords = [];
            $pageToken = null;
            $attempt = 0;

            do {
                $attempt++;
                Log::info("Fetch attempt #{$attempt}");

                $params = [
                    'page_size' => $pageSize,
                ];

                if ($pageToken) {
                    $params['page_token'] = $pageToken;
                }

                // Try endpoint with different API version
                $url = "https://open.larksuite.com/open-apis/bitable/v1/apps/{$this->baseId}/tables/{$this->tableId}/records";
                Log::info("Request URL: {$url}");
                Log::info('Request params: ' . json_encode($params));

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json; charset=utf-8',
                ])
                    ->timeout(60)
                    ->get($url, $params);

                Log::info('Response status: ' . $response->status());
                Log::info('Response body: ' . $response->body());

                if (!$response->successful()) {
                    Log::error('Failed to fetch records: ' . $response->body());

                    // Jika 403, kemungkinan app tidak punya akses ke base/table ini
                    if ($response->status() === 403) {
                        Log::error('403 Forbidden: App does not have access to this base/table');
                        Log::error('Make sure:');
                        Log::error('1. App is installed in workspace');
                        Log::error('2. App has bitable:app permission');
                        Log::error('3. Base/Table is accessible to app');
                    }

                    return null;
                }

                $data = $response->json();

                if (isset($data['code']) && $data['code'] !== 0) {
                    Log::error('Lark API error code: ' . $data['code']);
                    Log::error('Lark API error message: ' . ($data['msg'] ?? 'Unknown error'));
                    return null;
                }

                $records = $data['data']['items'] ?? [];
                $allRecords = array_merge($allRecords, $records);

                $pageToken = $data['data']['page_token'] ?? null;

                Log::info('Fetched ' . count($records) . ' records, total: ' . count($allRecords));
            } while ($pageToken && $attempt < 10);

            Log::info('Total records fetched: ' . count($allRecords));
            return $allRecords;
        } catch (\Exception $e) {
            Log::error('Exception fetching job orders: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    public function testConnection()
    {
        try {
            Log::info('=== Testing Lark Connection ===');

            // Test 1: Get access token
            $token = $this->getAccessToken();
            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'Failed to get access token',
                    'step' => 'authentication',
                ];
            }

            Log::info('✓ Access token obtained');

            // Test 2: Fetch 1 record
            Log::info('Attempting to fetch 1 record...');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json; charset=utf-8',
            ])
                ->timeout(30)
                ->get("https://open.larksuite.com/open-apis/bitable/v1/apps/{$this->baseId}/tables/{$this->tableId}/records", [
                    'page_size' => 1,
                ]);

            Log::info('Test response status: ' . $response->status());
            Log::info('Test response body: ' . $response->body());

            if (!$response->successful()) {
                $errorMsg = 'Failed to fetch records: ' . $response->body();

                if ($response->status() === 403) {
                    $errorMsg .= "\n\n⚠️ TROUBLESHOOTING:\n";
                    $errorMsg .= "1. Go to Lark Admin Console\n";
                    $errorMsg .= "2. Navigate to: Workplace → App Management\n";
                    $errorMsg .= "3. Find 'API For Job Order' app\n";
                    $errorMsg .= "4. Make sure it's INSTALLED (not just published)\n";
                    $errorMsg .= "5. Set visibility to 'All members'\n";
                    $errorMsg .= '6. OR grant specific base access via base settings';
                }

                return [
                    'success' => false,
                    'message' => $errorMsg,
                    'step' => 'records_access',
                ];
            }

            $data = $response->json();

            if (isset($data['code']) && $data['code'] !== 0) {
                return [
                    'success' => false,
                    'message' => 'Lark API error: ' . ($data['msg'] ?? 'Unknown error'),
                    'step' => 'api_error',
                    'error_code' => $data['code'],
                ];
            }

            $recordCount = count($data['data']['items'] ?? []);

            Log::info("✓ Connection successful! Found {$recordCount} record(s)");

            return [
                'success' => true,
                'message' => "Connection successful! Found {$recordCount} record(s) in first page.",
                'data' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('Exception testing connection: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'step' => 'exception',
            ];
        }
    }
}
