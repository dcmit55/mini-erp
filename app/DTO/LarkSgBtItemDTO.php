<?php

namespace App\DTO;

/**
 * Lark SG-BT Item Tracking DTO
 *
 * Maps raw data from Lark API (Singapore to Batam Items)
 * table_id: tbl43ITOZDDkUJ2Z
 * view_id: vewrGHKZN5
 */
class LarkSgBtItemDTO extends BaseLarkDTO
{
    public readonly ?string $itemNameRaw;
    public readonly ?string $statusRaw;
    public readonly ?int $qtyRaw;
    public readonly ?float $sgdCostRaw;
    public readonly ?string $projectLarkRaw;
    public readonly ?string $courierLinkRaw;

    protected const FIELD_MAPPING = [
        'lark_sg_bt_item_trackings.item_name' => 'Item Name (SG-BT)',
        'lark_sg_bt_item_trackings.status' => 'Status',
        'lark_sg_bt_item_trackings.qty' => 'QTY Line Item',
        'lark_sg_bt_item_trackings.sgd_cost' => 'SGD Cost',
        'lark_sg_bt_item_trackings.project_lark' => 'Link Project',
        'lark_sg_bt_item_trackings.courier_link' => 'Courier ID (SG-BT)',
    ];

    public function __construct(array $larkRecord)
    {
        parent::__construct($larkRecord);

        $fields = $larkRecord['fields'] ?? [];

        $this->itemNameRaw = $this->extractField($fields, 'lark_sg_bt_item_trackings.item_name');
        $this->statusRaw = $this->extractField($fields, 'lark_sg_bt_item_trackings.status');
        $this->qtyRaw = $this->extractInteger($fields, 'lark_sg_bt_item_trackings.qty');
        $this->sgdCostRaw = $this->extractNumeric($fields, 'lark_sg_bt_item_trackings.sgd_cost');
        $this->projectLarkRaw = $this->extractField($fields, 'lark_sg_bt_item_trackings.project_lark');
        $this->courierLinkRaw = $this->extractField($fields, 'lark_sg_bt_item_trackings.courier_link');
    }

    private function extractNumeric(array $fields, string $fieldName): ?float
    {
        $value = $this->extractField($fields, $fieldName);
        return is_numeric($value) ? (float) $value : null;
    }

    private function extractInteger(array $fields, string $fieldName): ?int
    {
        $value = $this->extractField($fields, $fieldName);

        // Handle array (sometimes Lark returns array instead of scalar)
        if (is_array($value)) {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
