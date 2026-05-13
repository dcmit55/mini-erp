<?php

namespace App\Services\Lark;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lark API Client
 *
 * Bertugas HANYA berkomunikasi dengan Lark API
 * TANGGUNG JAWAB:
 * - Get access token
 * - Fetch records dari Lark Base
 * - Handle API errors
 *
 * DILARANG:
 * - Parsing data
 * - Database operation
 * - Business logic
 */
class LarkApiClient
{
    private string $appId;
    private string $appSecret;
    private string $baseUrl = 'https://open.larksuite.com/open-apis';
    private bool $verifySsl;

    private ?string $accessToken = null;
    private ?int $tokenExpiry = null;

    public function __construct()
    {
        $this->appId     = config('services.lark.app_id');
        $this->appSecret = config('services.lark.app_secret');
        $this->verifySsl = (bool) config('lark.verify_ssl', true);
    }

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::timeout(30);
        if (!$this->verifySsl) {
            $client = $client->withoutVerifying();
        }
        return $client;
    }

    /**
     * Get tenant access token
     * Cache token sampai expire
     */
    private function getAccessToken(): string
    {
        // Return cached token jika masih valid
        if ($this->accessToken && $this->tokenExpiry && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        $response = $this->http()->post("{$this->baseUrl}/auth/v3/tenant_access_token/internal", [
            'app_id' => $this->appId,
            'app_secret' => $this->appSecret,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to get Lark access token: ' . $response->body());
        }

        $data = $response->json();

        if ($data['code'] !== 0) {
            throw new \Exception('Lark API error: ' . ($data['msg'] ?? 'Unknown error'));
        }

        $this->accessToken = $data['tenant_access_token'];
        $this->tokenExpiry = time() + ($data['expire'] ?? 7200) - 60; // Buffer 1 menit

        return $this->accessToken;
    }

    /**
     * Fetch all records from Lark Base table
     *
     * PENTING: Lark API punya 2 mode response untuk fields:
     * 1. field_key="name" (default) → Response pakai field_name ("Job Order Name / Description")
     * 2. field_key="id" → Response pakai field_id ("fld0e6YU25")
     *
     * Kita gunakan field_key="id" supaya mapping stabil (field_id tidak berubah kalau rename field)
     *
     * @param string $appToken Base ID
     * @param string $tableId Table ID
     * @param string|null $viewId View ID (optional)
     * @return array Raw response dari Lark
     */
    public function fetchRecords(string $appToken, string $tableId, ?string $viewId = null, string $fieldKey = 'id'): array
    {
        $token = $this->getAccessToken();
        $allRecords = [];
        $pageToken = null;

        do {
            $url = "{$this->baseUrl}/bitable/v1/apps/{$appToken}/tables/{$tableId}/records";

            $params = [
                'page_size' => 500,
                'field_key' => $fieldKey,
            ];

            if ($viewId) {
                $params['view_id'] = $viewId;
            }

            if ($pageToken) {
                $params['page_token'] = $pageToken;
            }

            Log::info('Fetching Lark records', [
                'url' => $url,
                'params' => $params,
            ]);

            $response = $this->http()->withToken($token)->get($url, $params);

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch Lark records: ' . $response->body());
            }

            $data = $response->json();

            if ($data['code'] !== 0) {
                throw new \Exception('Lark API error: ' . ($data['msg'] ?? 'Unknown error'));
            }

            $items = $data['data']['items'] ?? [];
            $allRecords = array_merge($allRecords, $items);

            // Check pagination
            $pageToken = $data['data']['page_token'] ?? null;
            $hasMore = $data['data']['has_more'] ?? false;
        } while ($hasMore && $pageToken);

        Log::info('Lark records fetched', [
            'total' => count($allRecords),
            'field_key' => $fieldKey,
        ]);

        return $allRecords;
    }

    /**
     * Download media/attachment from Lark Drive API with proper auth
     *
     * Lark attachment URL format:
     * https://open.larksuite.com/open-apis/drive/v1/medias/{fileToken}/download?extra=...
     *
     * Memerlukan Authorization: Bearer <tenant_access_token>
     *
     * @param string $url Full download URL dari Lark attachment
     * @return \Illuminate\Http\Client\Response|null
     */
    public function downloadMedia(string $url): ?\Illuminate\Http\Client\Response
    {
        $token = $this->getAccessToken();

        $response = $this->http()->withToken($token)->get($url);

        return $response;
    }

    /**
     * Get a pre-signed (publicly accessible) temporary download URL for a Lark file.
     *
     * Calls the batch_get_tmp_download_url endpoint with Bearer auth,
     * returns the tmp_download_url from the response — valid ~10-30 minutes,
     * accessible by browser WITHOUT auth.
     *
     * @param string $fileToken  Lark file token
     * @param string $extra      URL-encoded extra param (bitablePerm context)
     * @return string|null       Pre-signed URL usable as <img src> or null on failure
     */
    public function getTmpDownloadUrl(string $fileToken, string $extra = ''): ?string
    {
        $token = $this->getAccessToken();

        $url = "{$this->baseUrl}/drive/v1/medias/batch_get_tmp_download_url?file_tokens={$fileToken}";
        if ($extra) {
            $url .= '&extra=' . $extra;
        }

        $response = Http::withToken($token)->timeout(10)->get($url);

        if (!$response->successful()) {
            Log::warning('LarkApiClient: getTmpDownloadUrl failed', [
                'file_token' => $fileToken,
                'status' => $response->status(),
            ]);
            return null;
        }

        $data = $response->json();

        if (($data['code'] ?? -1) !== 0) {
            Log::warning('LarkApiClient: getTmpDownloadUrl API error', [
                'file_token' => $fileToken,
                'code' => $data['code'] ?? null,
                'msg' => $data['msg'] ?? null,
            ]);
            return null;
        }

        return $data['data']['tmp_download_urls'][0]['tmp_download_url'] ?? null;
    }

    /**
     * Pre-warm proxy cache for multiple Lark URLs using parallel HTTP calls.
     *
     * Calls the Lark batch_get_tmp_download_url endpoint concurrently for each URL
     * so that subsequent /lark-media proxy requests are instant cache hits (no API call needed).
     *
     * @param string[] $larkUrls  Array of Lark /download URLs stored in DB
     */
    public function prewarmBatch(array $larkUrls): void
    {
        // Only process URLs not already cached
        $toResolve = [];
        foreach ($larkUrls as $url) {
            $cacheKey = 'lark_media_' . md5($url);
            if (Cache::has($cacheKey)) {
                continue;
            }

            // Extract file token and extra param (keep extra URL-encoded)
            if (!preg_match('|/medias/([A-Za-z0-9_-]+)/download|', $url, $m)) {
                continue;
            }
            $fileToken = $m[1];
            $queryString = parse_url($url, PHP_URL_QUERY) ?? '';
            preg_match('/(?:^|&)extra=([^&]+)/', $queryString, $extraMatch);
            $extra = $extraMatch[1] ?? '';

            $toResolve[] = ['url' => $url, 'token' => $fileToken, 'extra' => $extra];
        }

        if (empty($toResolve)) {
            return;
        }

        try {
            $token = $this->getAccessToken();
            $baseUrl = $this->baseUrl;

            // Fire all Lark API calls in parallel via Http::pool()
            $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($toResolve, $token, $baseUrl) {
                $requests = [];
                foreach ($toResolve as $item) {
                    $apiUrl = "{$baseUrl}/drive/v1/medias/batch_get_tmp_download_url?file_tokens={$item['token']}";
                    if ($item['extra']) {
                        $apiUrl .= '&extra=' . $item['extra'];
                    }
                    $requests[] = $pool->withToken($token)->timeout(10)->get($apiUrl);
                }
                return $requests;
            });

            foreach ($toResolve as $idx => $item) {
                $response = $responses[$idx] ?? null;
                if (!$response || !$response->successful()) {
                    continue;
                }
                $data = $response->json();
                if (($data['code'] ?? -1) !== 0) {
                    continue;
                }
                $preSignedUrl = $data['data']['tmp_download_urls'][0]['tmp_download_url'] ?? null;
                if ($preSignedUrl) {
                    Cache::put('lark_media_' . md5($item['url']), $preSignedUrl, now()->addMinutes(10));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('LarkApiClient: prewarmBatch failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get raw response untuk debugging
     */
    public function fetchRawResponse(string $appToken, string $tableId, ?string $viewId = null, string $fieldKey = 'name'): array
    {
        return [
            'fetched_at' => now()->toIso8601String(),
            'app_token' => $appToken,
            'table_id' => $tableId,
            'view_id' => $viewId,
            'records' => $this->fetchRecords($appToken, $tableId, $viewId, $fieldKey),
        ];
    }
}
