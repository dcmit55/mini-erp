# ============================================================
# Webhook HMAC Test Script — VerifyWebhookHMAC Middleware
# ============================================================
# Jalankan: .\docs\webhook-test.ps1
# Format  : .\docs\webhook-test.ps1 -Format iso
# ============================================================

param(
    [string]$Format      = "unix",
    [string]$BaseUrl     = "http://localhost:8000",
    [string]$WebhookUuid = "b368aad6-87f7-4a78-9edb-001fcdf1e543",
    [string]$BearerToken = "2a6ed3ef8a9f7ae3b2744129b583d392f7f066f478e5fc0699413777b25a25d2",
    [string]$HmacSecret  = "3d4e60c6cdc64e16451cb61cfb265582c865c995a4a69e4a0329d0e1162fd8b3"
)

# ── Buat temporary file ─────────────────────────────────────────────────
$tmpFile = [System.IO.Path]::GetTempFileName()
$body = '{"device_id":"FP001","event_type":"checkin","fingerprint_data":{"user_id":"12345","template":"base64data"}}'

# Simpan body ke file dengan encoding UTF8 tanpa BOM
[System.IO.File]::WriteAllText($tmpFile, $body, [System.Text.UTF8Encoding]::new($false))

# ── Timestamp (selalu UTC) ─────────────────────────────────────────────
$nowUtc = [DateTimeOffset]::UtcNow
switch ($Format) {
    "unix"   { $timestamp = $nowUtc.ToUnixTimeSeconds().ToString() }
    "iso"    { $timestamp = $nowUtc.ToString("yyyy-MM-ddTHH:mm:ssZ") }
    "iso_ms" { $timestamp = $nowUtc.ToString("yyyy-MM-ddTHH:mm:ss.fffZ") }
    "sql"    { $timestamp = $nowUtc.ToString("yyyy-MM-dd HH:mm:ss") }
    default  { Write-Error "Format tidak dikenal: $Format"; exit 1 }
}

# ── Hitung HMAC ────────────────────────────────────────────────────────
$hmac       = New-Object System.Security.Cryptography.HMACSHA256
$hmac.Key   = [System.Text.Encoding]::UTF8.GetBytes($HmacSecret)
$message    = $timestamp + "." + $body
$sigBytes   = $hmac.ComputeHash([System.Text.Encoding]::UTF8.GetBytes($message))
$signature  = [System.BitConverter]::ToString($sigBytes).Replace("-", "").ToLower()

# ── Diagnostic output ──────────────────────────────────────────────────
Write-Host "══════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host " Webhook HMAC Diagnostic" -ForegroundColor Cyan
Write-Host "══════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "Format    : $Format"
Write-Host "Timestamp : $timestamp  (len=$($timestamp.Length))"
Write-Host "Body len  : $($body.Length) bytes"
Write-Host "Body[0:50]: $($body.Substring(0, [Math]::Min(50, $body.Length)))..."
Write-Host "Msg len   : $($message.Length) bytes"
Write-Host "Signature : $signature"
Write-Host ""

# ── Cross-check via PHP CLI ────────────────────────────────────────────
Write-Host "Cross-check via PHP CLI:" -ForegroundColor Yellow
$phpOutput = & php docs/hmac-check.php $timestamp $tmpFile 2>&1
$phpSig = ""

foreach ($line in $phpOutput) {
    if ($line -match "^[0-9a-f]{64}$") {
        $phpSig = $line.Trim()
        break
    }
}

if ($phpSig) {
    Write-Host "  PHP expected : $phpSig"
    Write-Host "  PS computed  : $signature"
    if ($phpSig -eq $signature) {
        Write-Host "  [MATCH] Signature konsisten" -ForegroundColor Green
    } else {
        Write-Host "  [MISMATCH] Ada perbedaan!" -ForegroundColor Red
        Write-Host "  Output PHP lengkap:" -ForegroundColor Yellow
        $phpOutput | ForEach-Object { Write-Host "    $_" }
        Remove-Item $tmpFile -Force -ErrorAction SilentlyContinue
        exit 1
    }
} else {
    Write-Host "  PHP check tidak bisa dijalankan — lanjut kirim request" -ForegroundColor Yellow
}

Write-Host ""

# ── Kirim request ──────────────────────────────────────────────────────
$url = "$BaseUrl/api/webhook/fingerprint/$WebhookUuid"
Write-Host "POST $url" -ForegroundColor Cyan

$result = curl.exe -s -w "`n%{http_code}" `
    -X POST $url `
    -H "Authorization: Bearer $BearerToken" `
    -H "Content-Type: application/json" `
    -H "X-Timestamp: $timestamp" `
    -H "X-Signature: $signature" `
    --data "@$tmpFile"

$lines      = $result -split "`n"
$statusCode = ($lines[-1]).Trim()
$respBody   = ($lines[0..($lines.Count - 2)] -join "`n").Trim()

Write-Host "Status    : $statusCode"
Write-Host "Response  : $respBody"

if ($statusCode -eq "200") {
    Write-Host "`n[OK] Webhook berhasil!" -ForegroundColor Green
} else {
    Write-Host "`n[FAIL] Signature tidak cocok — cek log:" -ForegroundColor Red
    Write-Host "  Get-Content storage/logs/laravel.log -Tail 50"
}

# ── Hapus temporary file ───────────────────────────────────────────────
Remove-Item $tmpFile -Force -ErrorAction SilentlyContinue