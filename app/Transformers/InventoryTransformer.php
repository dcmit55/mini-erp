<?php

namespace App\Transformers;

use App\DTO\LarkInventoryDTO;
use App\Models\Logistic\Unit;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Inventory Transformer
 *
 * Bertugas melakukan normalisasi data dari DTO ke format database
 * TANGGUNG JAWAB:
 * - Trim string
 * - Normalisasi nilai
 * - Konversi tipe data
 * - Validasi business rules
 *
 * DILARANG:
 * - Database operation
 * - API calls
 */
class InventoryTransformer
{
    /**
     * Transform Lark DTO to database-ready array
     *
     * @param LarkInventoryDTO $dto
     * @return array Data siap disimpan ke database
     */
    public function transform(LarkInventoryDTO $dto): array
    {
        return [
            'lark_record_id' => $dto->recordId,
            'name' => $this->normalizeName($dto->nameRaw),
            'project_lark' => $this->normalizeProjectLark($dto->projectLarkRaw),
            'quantity' => $this->normalizeQuantity($dto->quantityRaw),
            'unit' => $this->normalizeUnit($dto->unitRaw),
            'unit_id' => $this->findOrCreateUnitId($dto->unitRaw),
            'price' => $this->normalizePrice($dto->totalCostRmbRaw),
            'currency_id' => 6, // RMB currency ID (fixed as per requirement)
            'supplier_lark' => $this->normalizeSupplierLark($dto->supplierLarkRaw),
            'img' => $this->normalizeImageUrl($dto->itemPhotoRaw),
            'last_sync_at' => now(),
        ];
    }

    /**
     * Validate transformed data before saving
     *
     * @param array $data
     * @throws \InvalidArgumentException
     */
    public function validate(array $data): void
    {
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Item name cannot be empty');
        }

        if (!isset($data['quantity']) || $data['quantity'] < 0) {
            throw new \InvalidArgumentException('Quantity must be a non-negative number');
        }

        if (!isset($data['price']) || $data['price'] < 0) {
            throw new \InvalidArgumentException('Price must be a non-negative number');
        }
    }

    /**
     * Normalize item name
     */
    private function normalizeName(?string $value): string
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('Item name cannot be empty');
        }

        // Trim dan remove multiple spaces
        $normalized = trim(preg_replace('/\s+/', ' ', $value));

        // Limit panjang sesuai database
        return substr($normalized, 0, 255);
    }

    /**
     * Normalize project link dari Lark (staging data)
     */
    private function normalizeProjectLark(?string $value): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        return substr(trim($value), 0, 255);
    }

    /**
     * Normalize quantity
     */
    private function normalizeQuantity(?string $value): float
    {
        if (empty($value)) {
            return 0.0;
        }

        // Remove non-numeric characters kecuali dot dan minus
        $cleaned = preg_replace('/[^0-9.\-]/', '', $value);

        $quantity = (float) $cleaned;

        // Quantity tidak boleh negatif
        return max(0, $quantity);
    }

    /**
     * Normalize unit
     */
    private function normalizeUnit(?string $value): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        return substr(trim($value), 0, 50);
    }

    /**
     * Normalize price (Total Cost RMB)
     */
    private function normalizePrice(?string $value): float
    {
        if (empty($value)) {
            return 0.0;
        }

        // Remove non-numeric characters kecuali dot dan minus
        $cleaned = preg_replace('/[^0-9.\-]/', '', $value);

        $price = (float) $cleaned;

        // Price tidak boleh negatif
        return max(0, $price);
    }

    /**
     * Normalize supplier name dari Lark (staging data)
     */
    private function normalizeSupplierLark(?string $value): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        return substr(trim($value), 0, 255);
    }

    /**
     * Find or create Unit ID by name
     *
     * @param string|null $unitName
     * @return int|null Unit ID or null if unit name is empty
     */
    private function findOrCreateUnitId(?string $unitName): ?int
    {
        if (empty($unitName) || trim($unitName) === '') {
            return null;
        }

        $normalized = trim($unitName);

        // Case-insensitive lookup
        $unit = Unit::whereRaw('LOWER(name) = ?', [strtolower($normalized)])->first();

        if (!$unit) {
            $unit = Unit::create(['name' => $normalized]);
            \Log::info('Created new unit from Lark sync', ['unit_name' => $normalized]);
        }

        return $unit->id;
    }

    /**
     * Normalize image URL from Lark attachment
     *
     * Lark returns attachment as array: [{"url": "...", "tmp_url": "...", "file_token": "..."}]
     *
     * IMPORTANT: Lark URLs require authentication and expire.
     * We DOWNLOAD the image and save to local storage instead of storing the URL.
     *
     * @param array|null $attachments
     * @return string|null Local storage path or null
     */
    private function normalizeImageUrl(?array $attachments): ?string
    {
        if (empty($attachments) || !is_array($attachments)) {
            return null;
        }

        // Get first attachment
        $firstAttachment = $attachments[0] ?? null;
        if (!$firstAttachment || !is_array($firstAttachment)) {
            return null;
        }

        // Get URL (prefer 'url' over 'tmp_url')
        $larkUrl = $firstAttachment['url'] ?? ($firstAttachment['tmp_url'] ?? null);

        if (empty($larkUrl) || !is_string($larkUrl)) {
            \Log::warning('Lark image attachment without valid URL', [
                'attachment' => $firstAttachment,
            ]);
            return null;
        }

        try {
            // Download image from Lark
            // Note: Lark API might require authentication headers
            $response = Http::timeout(30)->get($larkUrl);

            if (!$response->successful()) {
                \Log::error('Failed to download Lark image', [
                    'url' => $larkUrl,
                    'status' => $response->status(),
                ]);
                return null;
            }

            // Generate unique filename
            $extension = $this->getExtensionFromUrl($larkUrl) ?? 'jpg';
            $filename = 'lark_' . Str::random(40) . '.' . $extension;
            $path = 'inventory_images/' . $filename;

            // Save to storage/app/public/inventory_images/
            Storage::disk('public')->put($path, $response->body());

            \Log::info('Lark image downloaded successfully', [
                'lark_url' => $larkUrl,
                'local_path' => $path,
            ]);

            return $path;
        } catch (\Exception $e) {
            \Log::error('Error downloading Lark image', [
                'url' => $larkUrl,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Extract file extension from URL
     */
    private function getExtensionFromUrl(string $url): ?string
    {
        // Try to get from URL path
        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if ($ext && in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                return strtolower($ext);
            }
        }

        // Default to jpg
        return 'jpg';
    }
}
