<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $fillable = [
        'name',
        'token',
        'is_active',
        'allowed_ips',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * Generate a secure random token
     */
    public static function generateToken(): string
    {
        return hash('sha256', Str::random(60));
    }

    /**
     * Create a new API token
     */
    public static function createToken(string $name, ?array $allowedIps = null): self
    {
        return self::create([
            'name' => $name,
            'token' => self::generateToken(),
            'allowed_ips' => $allowedIps ? implode(',', $allowedIps) : null,
            'is_active' => true,
        ]);
    }

    /**
     * Validate token and check if it's active
     */
    public static function isValid(string $token): bool
    {
        $apiToken = self::where('token', $token)
            ->where('is_active', true)
            ->first();

        if (!$apiToken) {
            return false;
        }

        // Update last used timestamp (async untuk performa)
        $apiToken->updateQuietly(['last_used_at' => now()]);

        return true;
    }

    /**
     * Check if IP is allowed (if IP whitelist is configured)
     */
    public function isIpAllowed(?string $ip): bool
    {
        if (!$this->allowed_ips) {
            return true; // No restriction
        }

        $allowedIps = explode(',', $this->allowed_ips);
        return in_array($ip, $allowedIps);
    }

    /**
     * Revoke token
     */
    public function revoke(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Activate token
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }
}