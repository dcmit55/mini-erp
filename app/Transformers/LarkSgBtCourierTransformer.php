<?php

namespace App\Transformers;

use App\DTO\LarkSgBtCourierDTO;
use Illuminate\Support\Facades\Log;

/**
 * Lark SG-BT Courier Transformer
 * Same logic as BT-SG but for opposite direction
 */
class LarkSgBtCourierTransformer
{
    public function transform(LarkSgBtCourierDTO $dto): array
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

    private function normalizeCourierId(?string $value): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        return substr(trim($value), 0, 100);
    }

    private function normalizeString(?string $value): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    private function normalizeDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return date('Y-m-d', $value / 1000);
        }

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

    private function normalizeProjectLark($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
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

    public function validate(array $data): void
    {
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Courier name cannot be empty');
        }

        if (empty($data['date'])) {
            throw new \InvalidArgumentException('Date cannot be empty');
        }
    }
}
