<?php

namespace App\DTO;

/**
 * Lark BT-SG Item Tracking DTO
 *
 * Maps raw data from Lark API (Batam to Singapore Items)
 * table_id: tbl0Z7U3UpjDm8S0
 * view_id: vewrGHKZN5
 */
class LarkBtSgItemDTO extends BaseLarkDTO
{
    public readonly ?string $itemNameRaw;
    public readonly ?string $statusRaw;
    public readonly ?int $qtyRaw;
    public readonly ?float $sgdCostRaw;
    public readonly ?string $projectLarkRaw;
    public readonly ?string $courierLinkRaw;

    protected const FIELD_MAPPING = [
        'lark_bt_sg_item_trackings.item_name' => 'Item Name (BT-SG)',
        'lark_bt_sg_item_trackings.status' => 'Item Status',
        'lark_bt_sg_item_trackings.qty' => 'QTY Line Item',
        'lark_bt_sg_item_trackings.sgd_cost' => 'SGD Cost',
        'lark_bt_sg_item_trackings.project_lark' => 'Link Project',
        'lark_bt_sg_item_trackings.courier_link' => 'Courier ID (BT-SG)',
    ];

    public function __construct(array $larkRecord)
    {
        parent::__construct($larkRecord);

        $fields = $larkRecord['fields'] ?? [];

        $this->itemNameRaw = $this->extractField($fields, 'lark_bt_sg_item_trackings.item_name');
        $this->statusRaw = $this->extractField($fields, 'lark_bt_sg_item_trackings.status');
        $this->qtyRaw = $this->extractInteger($fields, 'lark_bt_sg_item_trackings.qty');
        $this->sgdCostRaw = $this->extractNumeric($fields, 'lark_bt_sg_item_trackings.sgd_cost');
        $this->projectLarkRaw = $this->extractField($fields, 'lark_bt_sg_item_trackings.project_lark');
        $this->courierLinkRaw = $this->extractField($fields, 'lark_bt_sg_item_trackings.courier_link');
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
