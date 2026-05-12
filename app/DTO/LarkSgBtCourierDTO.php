<?php

namespace App\DTO;

/**
 * Lark SG-BT Courier DTO
 *
 * Maps raw data from Lark API (Singapore to Batam Courier)
 * table_id: tblt8ioLwKa1ZjVe
 * view_id: vew11b8N8m
 */
class LarkSgBtCourierDTO extends BaseLarkDTO
{
    public readonly ?string $courierIdRaw;
    public readonly ?string $typeMovementRaw;
    public readonly ?string $dateRaw;
    public readonly ?string $projectLarkRaw;
    public readonly ?float $transportCostRaw;
    public readonly ?float $baggageCostRaw;
    public readonly ?float $gstCostRaw;
    public readonly ?int $qtyTotalRaw;
    public readonly ?float $costPerItemRaw;

    protected const FIELD_MAPPING = [
        'lark_sg_bt_courier_ids.name' => 'Courier ID',
        'lark_sg_bt_courier_ids.type_movement' => 'Type of Movement',
        'lark_sg_bt_courier_ids.date' => 'Date', // Capital D!
        'lark_sg_bt_courier_ids.project_lark' => 'SG-BT',
        'lark_sg_bt_courier_ids.transport_cost' => 'SGD Transport Cost',
        'lark_sg_bt_courier_ids.baggage_cost' => 'SGD Baggage Cost',
        'lark_sg_bt_courier_ids.gst_cost' => 'SGD GST Cost',
        'lark_sg_bt_courier_ids.qty_total' => 'QTY Total',
        'lark_sg_bt_courier_ids.cost_per_item' => 'cost per item',
    ];

    public function __construct(array $larkRecord)
    {
        parent::__construct($larkRecord);

        $fields = $larkRecord['fields'] ?? [];

        $this->courierIdRaw = $this->extractField($fields, 'lark_sg_bt_courier_ids.name');
        $this->typeMovementRaw = $this->extractField($fields, 'lark_sg_bt_courier_ids.type_movement');
        $this->dateRaw = $this->extractField($fields, 'lark_sg_bt_courier_ids.date');
        $this->projectLarkRaw = $this->extractField($fields, 'lark_sg_bt_courier_ids.project_lark');
        $this->transportCostRaw = $this->extractNumeric($fields, 'lark_sg_bt_courier_ids.transport_cost');
        $this->baggageCostRaw = $this->extractNumeric($fields, 'lark_sg_bt_courier_ids.baggage_cost');
        $this->gstCostRaw = $this->extractNumeric($fields, 'lark_sg_bt_courier_ids.gst_cost');
        $this->qtyTotalRaw = $this->extractInteger($fields, 'lark_sg_bt_courier_ids.qty_total');
        $this->costPerItemRaw = $this->extractNumeric($fields, 'lark_sg_bt_courier_ids.cost_per_item');
    }

    private function extractNumeric(array $fields, string $fieldName): ?float
    {
        $value = $this->extractField($fields, $fieldName);
        return is_numeric($value) ? (float) $value : null;
    }

    private function extractInteger(array $fields, string $fieldName): ?int
    {
        $value = $this->extractField($fields, $fieldName);
        return is_numeric($value) ? (int) $value : null;
    }
}
