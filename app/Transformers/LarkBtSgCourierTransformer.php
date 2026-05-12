<?php

namespace App\Transformers;

use App\DTO\LarkBtSgCourierDTO;
use Illuminate\Support\Facades\Log;

/**
 * Lark BT-SG Courier Transformer
 *
 * Bertugas normalisasi data dari DTO ke format database
 * - Trim strings
 * - Convert timestamps
 * - Handle arrays/objects from Lark
 * - Normalize numeric values
 */
class LarkBtSgCourierTransformer
{
    /**
     * Transform DTO to database-ready array
     */
    public function transform(LarkBtSgCourierDTO $dto): array
    {
        return [
            'lark_record_id' => $dto->recordId,
            'name' => $this->normalizeCourierId($dto->courierIdRaw),
            'type_movement' => $this->normalizeString($dto->typeMovementRaw),
            'date' => $this->normalizeDate($dto->dateRaw),
            'project_lark' => $this->normalizeProjectLark($dto->projectLarkRaw),
            'transport_cost' => $dto->transportCostRaw,
            'baggage_cost' => $dto->baggageCostRaw,
            'gst_cost' => $dto->gstCostRaw,
            'qty_total' => $dto->qtyTotalRaw,
            'cost_per_item' => $dto->costPerItemRaw,
            'last_sync_at' => now(),
        ];
    }

    /**
     * Normalize courier ID
     */
    private function normalizeCourierId(?string $value): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        return substr(trim($value), 0, 100);
    }

    /**
     * Normalize generic string field
     */
    private function normalizeString(?string $value): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    /**
     * Normalize date from Lark
     * Lark sends timestamp in milliseconds or already formatted
     */
    private function normalizeDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // If numeric (timestamp in milliseconds)
        if (is_numeric($value)) {
            return date('Y-m-d', $value / 1000);
        }

        // If already string date
        if (is_string($value)) {
            try {
                return date('Y-m-d', strtotime($value));
            } catch (\Exception $e) {
                Log::warning('Failed to parse date', ['value' => $value]);
                return null;
            }
        }

        return null;
    }

    /**
     * Normalize project_lark field
     * BT-SG field is array of record references - extract text or store as JSON
     */
    private function normalizeProjectLark($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // If already JSON string from DTO
        if (is_string($value)) {
            // Check if it's JSON
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // Extract text values from array
                $texts = [];
                foreach ($decoded as $item) {
                    if (isset($item['text'])) {
                        $texts[] = $item['text'];
                    }
                }
                return !empty($texts) ? implode(', ', $texts) : null;
            }

            return substr($value, 0, 500);
        }

        // If array (shouldn't happen if DTO works correctly)
        if (is_array($value)) {
            $texts = [];
            foreach ($value as $item) {
                if (isset($item['text'])) {
                    $texts[] = $item['text'];
                }
            }
            return !empty($texts) ? implode(', ', $texts) : null;
        }

        return null;
    }

    /**
     * Validate transformed data
     */
    public function validate(array $data): void
    {
        // Name is required
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Courier name cannot be empty');
        }

        // Date is required
        if (empty($data['date'])) {
            throw new \InvalidArgumentException('Date cannot be empty');
        }
    }
}
