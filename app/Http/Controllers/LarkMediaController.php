<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\Lark\LarkApiClient;

/**
 * LarkMediaController
 *
 * Proxy server-side untuk menampilkan gambar dari Lark Drive ke browser.
 *
 * KENAPA PERLU PROXY?
 * Lark attachment URLs (baik /download maupun batch_get_tmp_download_url)
 * memerlukan Authorization: Bearer {token} yang tidak bisa dikirim oleh
 * browser melalui <img src>. Proxy ini:
 * 1. Menerima request dari browser (dengan session auth)
 * 2. Memanggil Lark API server-side dengan Bearer token
 * 3. Mengambil pre-signed URL (tmp_download_url) yang bisa diakses tanpa auth
 * 4. Redirect browser ke pre-signed URL tersebut
 * 5. Cache pre-signed URL selama 10 menit untuk efisiensi
 */
class LarkMediaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Serve Lark media via proxy.
     *
     * URL parameter 'u' = base64_encode dari Lark attachment URL yang tersimpan di DB.
     * Hanya URL dari domain open.larksuite.com yang diizinkan (security check).
     */
    public function serve(Request $request, LarkApiClient $apiClient)
    {
        $encoded = $request->get('u');
        if (!$encoded) {
            abort(404);
        }

        $larkUrl = base64_decode($encoded);

        // Security: hanya izinkan URL dari Lark
        if (!$larkUrl || !str_contains($larkUrl, 'open.larksuite.com/open-apis/drive/')) {
            abort(404);
        }

        $cacheKey = 'lark_media_' . md5($larkUrl);

        // Cek cache: sudah ada pre-signed URL yang valid?
        $preSignedUrl = Cache::get($cacheKey);

        if (!$preSignedUrl) {
            $preSignedUrl = $this->resolvePreSignedUrl($larkUrl, $apiClient);

            if ($preSignedUrl) {
                // Cache 10 menit (Lark pre-signed URL biasanya valid 10-30 menit)
                Cache::put($cacheKey, $preSignedUrl, now()->addMinutes(10));
            }
        }

        if ($preSignedUrl) {
            // Redirect ke pre-signed URL — browser load langsung tanpa auth
            return redirect($preSignedUrl);
        }

        // Fallback: stream binary langsung (jika batch API gagal)
        $response = $apiClient->downloadMedia($larkUrl);

        if (!$response || !$response->successful()) {
            Log::warning('LarkMediaProxy: failed to fetch media', ['url' => $larkUrl]);
            abort(404);
        }

        $contentType = $response->header('Content-Type') ?: 'image/jpeg';
        return response($response->body(), 200, [
            'Content-Type'  => $contentType,
            'Cache-Control' => 'private, max-age=600', // 10 menit browser cache
        ]);
    }

    /**
     * Resolve pre-signed URL via Lark batch_get_tmp_download_url API.
     *
     * Mendukung 2 format URL yang tersimpan di DB:
     * Format A: .../drive/v1/medias/{fileToken}/download?extra=...
     * Format B: .../drive/v1/medias/batch_get_tmp_download_url?file_tokens={fileToken}&extra=...
     */
    private function resolvePreSignedUrl(string $larkUrl, LarkApiClient $apiClient): ?string
    {
        $fileToken = null;
        $extra = '';

        // Format A: /medias/{token}/download?extra=...
        if (preg_match('|/medias/([A-Za-z0-9_-]+)/download|', $larkUrl, $m)) {
            $fileToken = $m[1];
            // Keep extra URL-encoded (don't use parse_str — it decodes the value)
            $queryString = parse_url($larkUrl, PHP_URL_QUERY) ?? '';
            preg_match('/(?:^|&)extra=([^&]+)/', $queryString, $extraMatch);
            $extra = $extraMatch[1] ?? '';
        }
        // Format B: batch_get_tmp_download_url?file_tokens={token}&extra=...
        elseif (str_contains($larkUrl, 'batch_get_tmp_download_url')) {
            $queryString = parse_url($larkUrl, PHP_URL_QUERY) ?? '';
            preg_match('/(?:^|&)file_tokens=([^&]+)/', $queryString, $tokenMatch);
            $fileToken = $tokenMatch[1] ?? null;
            preg_match('/(?:^|&)extra=([^&]+)/', $queryString, $extraMatch);
            $extra = $extraMatch[1] ?? '';
        }

        if (!$fileToken) {
            return null;
        }

        return $apiClient->getTmpDownloadUrl($fileToken, $extra);
    }
}
