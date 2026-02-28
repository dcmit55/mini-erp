<?php

namespace App\Transformers;

use App\DTO\LarkSgBtItemDTO;
use App\Models\Production\Project;
use App\Models\Lark\LarkSgBtCourierId;

/**
 * Lark SG-BT Item Transformer
 */
class LarkSgBtItemTransformer
{
    public function transform(LarkSgBtItemDTO $dto): array
    {
        $projectName = $this->normalizeString($dto->projectLarkRaw);
        $projectId = $this->matchProject($projectName);

        $courierName = $this->normalizeString($dto->courierLinkRaw);
        $courierId = $this->matchCourier($courierName);

        return [
            'lark_record_id' => $dto->recordId,
            'item_name' => $this->normalizeItemName($dto->itemNameRaw),
            'status' => $this->normalizeString($dto->statusRaw),
            'qty' => $dto->qtyRaw,
            'sgd_cost' => $dto->sgdCostRaw,
            'project_lark' => $projectName,
            'project_id' => $projectId,
            'courier_id' => $courierId,
            'last_sync_at' => now(),
        ];
    }

    private function normalizeItemName(?string $value): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        return substr(trim($value), 0, 255);
    }

    private function normalizeString(?string $value): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    /**
     * Match project name with projects table
     * Return project_id if found, null otherwise
     */
    private function matchProject(?string $projectName): ?int
    {
        if (empty($projectName)) {
            return null;
        }

        // Try exact match first
        $project = Project::where('name', $projectName)->first();

        if ($project) {
            return $project->id;
        }

        // Try case-insensitive match
        $project = Project::whereRaw('LOWER(name) = ?', [strtolower($projectName)])->first();

        return $project?->id;
    }

    /**
     * Match courier name with lark_sg_bt_courier_ids table
     * Return courier_id if found, null otherwise
     */
    private function matchCourier(?string $courierName): ?int
    {
        if (empty($courierName)) {
            return null;
        }

        // Try exact match by name
        $courier = LarkSgBtCourierId::where('name', $courierName)->first();

        if ($courier) {
            return $courier->id;
        }

        // Try case-insensitive match
        $courier = LarkSgBtCourierId::whereRaw('LOWER(name) = ?', [strtolower($courierName)])->first();

        return $courier?->id;
    }

    public function validate(array $data): void
    {
        if (empty($data['item_name'])) {
            throw new \InvalidArgumentException('Item name cannot be empty');
        }
    }
}
