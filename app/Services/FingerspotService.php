<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FingerspotService
{
    protected string $apiToken;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiToken = config('fingerspot.api_token', '');
        $this->baseUrl  = rtrim(config('fingerspot.base_url', 'https://developer.fingerspot.io/api'), '/');
    }

    // ─── Internal ────────────────────────────────────────────────────────────

    private function sendCommand(string $endpoint, array $data): array
    {
        Log::info('Fingerspot API request', ['endpoint' => $endpoint, 'data' => $data]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Content-Type'  => 'application/json',
        ])->timeout(15)->post($this->baseUrl . $endpoint, $data);

        Log::info('Fingerspot API response', [
            'endpoint' => $endpoint,
            'status'   => $response->status(),
            'body'     => $response->body(),
        ]);

        if ($response->failed()) {
            Log::error('Fingerspot API error', [
                'endpoint' => $endpoint,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);
            throw new \Exception('Fingerspot API error (' . $response->status() . '): ' . $response->body());
        }

        $json = $response->json() ?? [];

        // Fingerspot API selalu return HTTP 200, tapi bisa success=false di body
        if (array_key_exists('success', $json) && $json['success'] === false) {
            $msg = $json['msg'] ?? $json['message'] ?? $json['info'] ?? $response->body();
            Log::error('Fingerspot API returned failure', [
                'endpoint' => $endpoint,
                'body'     => $response->body(),
            ]);
            throw new \Exception('Fingerspot device error: ' . $msg);
        }

        return $json;
    }

    private function transId(): string
    {
        return uniqid('dcm_', true);
    }

    // ─── Public API methods ──────────────────────────────────────────────────

    /**
     * Ambil data absensi dari mesin
     */
    public function getAttlog(string $cloudId, string $startDate, string $endDate): array
    {
        return $this->sendCommand('/get_attlog', [
            'trans_id'   => $this->transId(),
            'cloud_id'   => $cloudId,
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ]);
    }

    /**
     * Ambil info user dari mesin
     */
    public function getUserinfo(string $cloudId, string $pin): array
    {
        return $this->sendCommand('/get_userinfo', [
            'trans_id' => $this->transId(),
            'cloud_id' => $cloudId,
            'pin'      => $pin,
        ]);
    }


    /**
     * Kirim data user ke mesin (registrasi / update)
     */
    public function setUserinfo(string $cloudId, array $userData): array
    {
        return $this->sendCommand('/set_userinfo', [
            'trans_id' => $this->transId(),
            'cloud_id' => $cloudId,
            'data'     => $userData,
        ]);
    }

    /**
     * Hapus user dari mesin
     */
    public function deleteUserinfo(string $cloudId, string $pin): array
    {
        return $this->sendCommand('/delete_userinfo', [
            'trans_id' => $this->transId(),
            'cloud_id' => $cloudId,
            'pin'      => $pin,
        ]);
    }

    /**
     * Ambil semua PIN user dari mesin.
     * API bersifat async — mesin akan mengirim balik data via webhook.
     * $transId opsional; jika tidak diberikan, dibuat otomatis.
     */
    public function getAllPin(string $cloudId, ?string $transId = null): array
    {
        return $this->sendCommand('/get_all_pin', [
            'trans_id' => $transId ?? $this->transId(),
            'cloud_id' => $cloudId,
        ]);
    }

    /**
     * Set waktu / timezone mesin
     */
    public function setTime(string $cloudId, string $timezone): array
    {
        return $this->sendCommand('/set_time', [
            'trans_id' => $this->transId(),
            'cloud_id' => $cloudId,
            'timezone' => $timezone,
        ]);
    }

    /**
     * Registrasi biometrik secara online
     * verification: 0-9 = jari, 12 = wajah, 13 = vein
     */
    public function registerOnline(string $cloudId, string $pin, int $verification): array
    {
        return $this->sendCommand('/reg_online', [
            'trans_id'     => $this->transId(),
            'cloud_id'     => $cloudId,
            'pin'          => $pin,
            'verification' => $verification,
        ]);
    }

    /**
     * Restart mesin
     */
    public function restartDevice(string $cloudId): array
    {
        return $this->sendCommand('/restart_device', [
            'trans_id' => $this->transId(),
            'cloud_id' => $cloudId,
        ]);
    }

    /**
     * Info detail mesin
     */
    public function getDevice(string $cloudId): array
    {
        return $this->sendCommand('/get_device', [
            'trans_id' => $this->transId(),
            'cloud_id' => $cloudId,
        ]);
    }
}
