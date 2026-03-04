<?php

namespace App\Http\Middleware;

use Closure;
use DateTime;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyWebhookHMAC
{
    /**
     * Toleransi selisih timestamp antara pengirim dan server (detik).
     * Melindungi dari replay attack.
     */
    private const TIMESTAMP_TOLERANCE = 300; // 5 menit

    /**
     * Format datetime yang diterima oleh PHP DateTime::__construct().
     * Digunakan untuk parsing string timestamp non-Unix.
     *
     * Contoh yang diterima:
     *   Unix      : "1741234567"
     *   ISO 8601  : "2024-03-04T08:00:00Z"
     *   ISO + ms  : "2024-03-04T08:00:00.000Z"
     *   ISO + tz  : "2024-03-04T08:00:00+07:00"
     *   SQL       : "2024-03-04 08:00:00"
     */

    public function handle(Request $request, Closure $next)
    {
        $rawSignature = $request->header('X-Signature');
        $rawTimestamp = $request->header('X-Timestamp');

        // ── 1. Header wajib ada ──────────────────────────────────────────────
        if (!$rawSignature || !$rawTimestamp) {
            Log::warning('WebhookHMAC: Missing required headers', [
                'ip'            => $request->ip(),
                'has_signature' => !empty($rawSignature),
                'has_timestamp' => !empty($rawTimestamp),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Missing required headers: X-Signature, X-Timestamp',
            ], 401);
        }

        // ── 2. Parse timestamp ke Unix (menerima berbagai format) ────────────
        $unixTimestamp = $this->parseToUnix($rawTimestamp);

        if ($unixTimestamp === null) {
            Log::warning('WebhookHMAC: Invalid timestamp format', [
                'ip'            => $request->ip(),
                'timestamp_raw' => $rawTimestamp,
                'timestamp_len' => strlen($rawTimestamp),
                'hint'          => 'Format diterima: Unix integer, ISO 8601, Y-m-d H:i:s',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid timestamp format',
            ], 401);
        }

        // ── 3. Tolak timestamp expired (replay attack prevention) ────────────
        $diff = abs(time() - $unixTimestamp);
        if ($diff > self::TIMESTAMP_TOLERANCE) {
            Log::warning('WebhookHMAC: Timestamp expired', [
                'ip'           => $request->ip(),
                'diff_seconds' => $diff,
                'tolerance'    => self::TIMESTAMP_TOLERANCE,
                'unix_parsed'  => $unixTimestamp,
                'raw_received' => $rawTimestamp,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Request timestamp expired',
            ], 401);
        }

        // ── 4. Verifikasi HMAC-SHA256 ─────────────────────────────────────────
        // PENTING: HMAC dihitung dari RAW string timestamp (bukan Unix hasil parse).
        // Client harus menggunakan string yang SAMA persis seperti yang dikirim
        // di header X-Timestamp untuk menghitung signature.
        //
        // Formula: HMAC-SHA256( "<X-Timestamp>.<raw_body>", WEBHOOK_SECRET )
        $secret   = config('services.webhook.secret');
        $rawBody  = $request->getContent();
        $message  = $rawTimestamp . '.' . $rawBody;
        $expected = hash_hmac('sha256', $message, (string) $secret);

        // hash_equals() → constant-time comparison (mencegah timing attack)
        $receivedNormalized = strtolower((string) $rawSignature);

        if (!hash_equals($expected, $receivedNormalized)) {
            // ── Log forensik: semua nilai yang dipakai untuk diagnosa ─────────
            // Periksa storage/logs/laravel.log setelah request gagal.
            Log::warning('WebhookHMAC: Invalid signature', [
                'ip' => $request->ip(),

                // Timestamp — cek hidden chars, panjang, encoding
                'ts_raw'          => $rawTimestamp,
                'ts_hex'          => bin2hex($rawTimestamp),         // deteksi BOM/whitespace
                'ts_len'          => strlen($rawTimestamp),

                // Body — cek panjang, karakter awal/akhir
                'body_len'        => strlen($rawBody),
                'body_first50'    => substr($rawBody, 0, 50),
                'body_last10_hex' => bin2hex(substr($rawBody, -10)), // deteksi trailing \r\n

                // Message yang di-hash
                'msg_len'         => strlen($message),
                'msg_first60'     => substr($message, 0, 60),

                // Perbandingan signature — lihat di mana perbedaannya
                'sig_expected'    => $expected,
                'sig_received'    => $receivedNormalized,
                'sig_match_len'   => strlen(rtrim($expected ^ $receivedNormalized, "\0")),

                // Secret (jangan log nilai penuh — cukup panjang + prefix)
                'secret_len'      => strlen((string) $secret),
                'secret_prefix'   => substr((string) $secret, 0, 6) . '...',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid signature',
            ], 401);
        }

        return $next($request);
    }

    /**
     * Parse berbagai format timestamp ke Unix timestamp (integer detik).
     *
     * Format yang didukung:
     *   - Unix integer   : "1741234567"
     *   - ISO 8601 + Z   : "2024-03-04T08:00:00Z"
     *   - ISO 8601 + ms  : "2024-03-04T08:00:00.000Z"
     *   - ISO 8601 + tz  : "2024-03-04T08:00:00+07:00"
     *   - SQL datetime   : "2024-03-04 08:00:00"
     *
     * Return null jika format tidak dikenali.
     */
    private function parseToUnix(string $raw): ?int
    {
        $trimmed = trim($raw);

        if ($trimmed === '') {
            return null;
        }

        // ── Unix timestamp: string berisi digit saja ─────────────────────────
        if (ctype_digit($trimmed)) {
            $ts = (int) $trimmed;
            // Sanity check: tahun 2020–2100 (1577836800 – 4102444800)
            if ($ts < 1577836800 || $ts > 4102444800) {
                return null;
            }
            return $ts;
        }

        // ── String timestamp: ISO 8601, SQL datetime, dsb ────────────────────
        // Strip fractional seconds (.123) sebelum parsing — strtotime tidak
        // selalu mengenali bagian desimal pada semua format timestamp.
        $normalized = preg_replace('/\.\d+/', '', $trimmed);

        // strtotime() jauh lebih toleran terhadap variasi format dibanding
        // new DateTime(). Mengembalikan false jika string tidak dikenali.
        $ts = strtotime($normalized);

        if ($ts === false) {
            return null;
        }

        return $ts;
    }
}
