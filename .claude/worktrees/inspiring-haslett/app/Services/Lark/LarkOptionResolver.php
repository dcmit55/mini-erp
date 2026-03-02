<?php

namespace App\Services\Lark;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Lark Option Resolver
 *
 * Service untuk resolve option IDs menjadi readable text
 * Digunakan untuk Single/Multi Select fields yang return option IDs
 *
 * Cache mapping selama 24 jam untuk menghindari API calls berlebihan
 */
class LarkOptionResolver
{
    private LarkApiClient $apiClient;
    private string $baseId;

    public function __construct(LarkApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
        $this->baseId = config('services.lark.base_id');
    }

    /**
     * Resolve option IDs to text values
     *
     * @param array $optionIds Array of option IDs: ["optXXX", "optYYY"]
     * @param string $tableId Lark table ID
     * @param string $fieldId Lark field ID
     * @return array Array of resolved text values
     */
    public function resolveOptions(array $optionIds, string $tableId, string $fieldId): array
    {
        if (empty($optionIds)) {
            return [];
        }

        // Get mapping from cache or fetch from API
        $mapping = $this->getOptionMapping($tableId, $fieldId);

        // Resolve IDs to text
        $resolved = [];
        foreach ($optionIds as $optionId) {
            if (is_string($optionId) && isset($mapping[$optionId])) {
                $resolved[] = $mapping[$optionId];
            } else {
                // Keep original if not found in mapping
                $resolved[] = $optionId;
            }
        }

        return $resolved;
    }

    /**
     * Get option ID to text mapping for a specific field
     * Cached for 24 hours
     *
     * @param string $tableId
     * @param string $fieldId
     * @return array Mapping: ["optXXX" => "Text Value", ...]
     */
    private function getOptionMapping(string $tableId, string $fieldId): array
    {
        $cacheKey = "lark_options_{$this->baseId}_{$tableId}_{$fieldId}";

        return Cache::remember($cacheKey, 60 * 60 * 24, function () use ($tableId, $fieldId) {
            return $this->fetchOptionMapping($tableId, $fieldId);
        });
    }

    /**
     * Fetch option mapping from Lark API
     *
     * @param string $tableId
     * @param string $fieldId
     * @return array
     */
    private function fetchOptionMapping(string $tableId, string $fieldId): array
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)->get("https://open.larksuite.com/open-apis/bitable/v1/apps/{$this->baseId}/tables/{$tableId}/fields");

            if (!$response->successful()) {
                \Log::warning('Failed to fetch Lark field metadata', [
                    'table_id' => $tableId,
                    'field_id' => $fieldId,
                    'response' => $response->body(),
                ]);
                return [];
            }

            $data = $response->json();
            $fields = $data['data']['items'] ?? [];

            // Find the target field
            foreach ($fields as $field) {
                if ($field['field_id'] === $fieldId) {
                    $options = $field['property']['options'] ?? [];

                    // Build mapping
                    $mapping = [];
                    foreach ($options as $option) {
                        if (isset($option['id']) && isset($option['name'])) {
                            $mapping[$option['id']] = $option['name'];
                        }
                    }

                    return $mapping;
                }
            }

            \Log::warning('Field not found in Lark table', [
                'table_id' => $tableId,
                'field_id' => $fieldId,
            ]);

            return [];
        } catch (\Exception $e) {
            \Log::error('Error fetching Lark option mapping', [
                'table_id' => $tableId,
                'field_id' => $fieldId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get access token (reuse from LarkApiClient)
     */
    private function getAccessToken(): string
    {
        $response = Http::post('https://open.larksuite.com/open-apis/auth/v3/tenant_access_token/internal', [
            'app_id' => config('services.lark.app_id'),
            'app_secret' => config('services.lark.app_secret'),
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to get Lark access token: ' . $response->body());
        }

        $data = $response->json();

        if ($data['code'] !== 0) {
            throw new \Exception('Lark API error: ' . ($data['msg'] ?? 'Unknown error'));
        }

        return $data['tenant_access_token'];
    }

    /**
     * Clear cached options for a field
     *
     * @param string $tableId
     * @param string $fieldId
     */
    public function clearCache(string $tableId, string $fieldId): void
    {
        $cacheKey = "lark_options_{$this->baseId}_{$tableId}_{$fieldId}";
        Cache::forget($cacheKey);
    }
}
