<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\VerifyWebhookHMAC;
use Illuminate\Http\Request;
use Tests\TestCase;

class VerifyWebhookHMACTest extends TestCase
{
    private string $secret = 'test-secret-key-for-unit-test';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.webhook.secret' => $this->secret]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeRequest(array $headers = [], string $body = '{}'): Request
    {
        $request = Request::create('/api/webhook/test', 'POST', [], [], [], [], $body);
        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }
        return $request;
    }

    /** Hitung signature menggunakan raw timestamp string (bukan Unix hasil parse) */
    private function sign(string $rawTimestamp, string $body): string
    {
        return hash_hmac('sha256', $rawTimestamp . '.' . $body, $this->secret);
    }

    private function runMiddleware(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        return (new VerifyWebhookHMAC())->handle(
            $request,
            fn($req) => response()->json(['success' => true])
        );
    }

    // ── Format timestamp yang valid ───────────────────────────────────────────

    /** Unix timestamp saat ini → lolos */
    public function test_unix_timestamp_passes(): void
    {
        $timestamp = (string) time();
        $body      = '{"visitor_id":"abc123"}';

        $response = $this->runMiddleware($this->makeRequest([
            'X-Timestamp' => $timestamp,
            'X-Signature' => $this->sign($timestamp, $body),
        ], $body));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** ISO 8601 dengan Z (UTC) → lolos */
    public function test_iso8601_utc_passes(): void
    {
        $timestamp = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z');
        $body      = '{"visitor_id":"abc123"}';

        $response = $this->runMiddleware($this->makeRequest([
            'X-Timestamp' => $timestamp,
            'X-Signature' => $this->sign($timestamp, $body),
        ], $body));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** ISO 8601 dengan millisecond → lolos */
    public function test_iso8601_with_milliseconds_passes(): void
    {
        // Wajib gunakan UTC agar literal "Z" di akhir string konsisten
        // new DateTime() tanpa timezone = local time → Z suffix = salah timezone
        $timestamp = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z');
        $body      = '{"visitor_id":"abc123"}';

        $response = $this->runMiddleware($this->makeRequest([
            'X-Timestamp' => $timestamp,
            'X-Signature' => $this->sign($timestamp, $body),
        ], $body));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** ISO 8601 dengan timezone offset → lolos */
    public function test_iso8601_with_timezone_offset_passes(): void
    {
        $timestamp = (new \DateTime('now', new \DateTimeZone('+07:00')))->format(\DateTimeInterface::RFC3339);
        $body      = '{"visitor_id":"abc123"}';

        $response = $this->runMiddleware($this->makeRequest([
            'X-Timestamp' => $timestamp,
            'X-Signature' => $this->sign($timestamp, $body),
        ], $body));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** SQL datetime format → lolos */
    public function test_sql_datetime_format_passes(): void
    {
        $timestamp = (new \DateTime())->format('Y-m-d H:i:s');
        $body      = '{"visitor_id":"abc123"}';

        $response = $this->runMiddleware($this->makeRequest([
            'X-Timestamp' => $timestamp,
            'X-Signature' => $this->sign($timestamp, $body),
        ], $body));

        $this->assertEquals(200, $response->getStatusCode());
    }

    // ── Rejection cases ───────────────────────────────────────────────────────

    /** Signature salah → 401 */
    public function test_invalid_signature_is_rejected(): void
    {
        $timestamp = (string) time();

        $response = $this->runMiddleware($this->makeRequest([
            'X-Timestamp' => $timestamp,
            'X-Signature' => str_repeat('a', 64),
        ], '{"visitor_id":"abc123"}'));

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Invalid signature', json_decode($response->getContent())->message);
    }

    /** Timestamp Unix expired (>5 menit) → 401 */
    public function test_expired_unix_timestamp_is_rejected(): void
    {
        $timestamp = (string) (time() - 301);
        $body      = '{"visitor_id":"abc123"}';

        $response = $this->runMiddleware($this->makeRequest([
            'X-Timestamp' => $timestamp,
            'X-Signature' => $this->sign($timestamp, $body),
        ], $body));

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Request timestamp expired', json_decode($response->getContent())->message);
    }

    /** Timestamp ISO 8601 expired → 401 */
    public function test_expired_iso_timestamp_is_rejected(): void
    {
        $dt        = new \DateTime('-10 minutes', new \DateTimeZone('UTC'));
        $timestamp = $dt->format('Y-m-d\TH:i:s\Z');
        $body      = '{"visitor_id":"abc123"}';

        $response = $this->runMiddleware($this->makeRequest([
            'X-Timestamp' => $timestamp,
            'X-Signature' => $this->sign($timestamp, $body),
        ], $body));

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Request timestamp expired', json_decode($response->getContent())->message);
    }

    /** String acak bukan timestamp → 401 */
    public function test_gibberish_timestamp_is_rejected(): void
    {
        $response = $this->runMiddleware($this->makeRequest([
            'X-Timestamp' => 'not-a-timestamp-at-all',
            'X-Signature' => 'doesnotmatter',
        ], '{}'));

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Invalid timestamp format', json_decode($response->getContent())->message);
    }

    /** Tidak ada header sama sekali → 401 */
    public function test_missing_both_headers_is_rejected(): void
    {
        $response = $this->runMiddleware($this->makeRequest([], '{"visitor_id":"abc123"}'));

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Missing required headers', json_decode($response->getContent())->message);
    }

    /** Body diubah setelah signing (tamper detection) → 401 */
    public function test_tampered_body_is_rejected(): void
    {
        $timestamp    = (string) time();
        $originalBody = '{"visitor_id":"abc123"}';
        $tamperedBody = '{"visitor_id":"hacker"}';

        $response = $this->runMiddleware($this->makeRequest([
            'X-Timestamp' => $timestamp,
            'X-Signature' => $this->sign($timestamp, $originalBody),
        ], $tamperedBody));

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Invalid signature', json_decode($response->getContent())->message);
    }

    /** Unix timestamp terlalu jauh di masa lalu (sebelum 2020) → 401 */
    public function test_unix_timestamp_before_2020_is_rejected(): void
    {
        $timestamp = '1000000000'; // 2001
        $body      = '{}';

        $response = $this->runMiddleware($this->makeRequest([
            'X-Timestamp' => $timestamp,
            'X-Signature' => $this->sign($timestamp, $body),
        ], $body));

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Invalid timestamp format', json_decode($response->getContent())->message);
    }
}
