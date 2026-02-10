<?php

namespace App\DTO;

/**
 * Lark Inventory Data Transfer Object
 *
 * Bertugas HANYA memetakan data mentah dari Lark API
 * DILARANG: database operation, normalisasi, business logic
 *
 * Extends BaseLarkDTO untuk reusable extractField() method
 */
class LarkInventoryDTO extends BaseLarkDTO
{
    public readonly ?string $nameRaw;
    public readonly ?string $projectLarkRaw;
    public readonly ?string $quantityRaw;
    public readonly ?string $unitRaw;
    public readonly ?string $totalCostRmbRaw;
    public readonly ?string $supplierLarkRaw;
    public readonly ?string $destinationRaw;
    public readonly ?string $statusRaw;
    public readonly ?array $itemPhotoRaw;

    /**
     * Field mapping dari internal key → Lark field_name
     *
     * PENTING: Lark API mengembalikan field_name (bukan field_id) di response.
     *
     * Field Names dari Lark Base (Inventory Listing table, view_id: vewEW56Qcr):
     * - "Item Requested"    → inventories.name
     * - "Link Project"      → inventories.project_lark
     * - "Quantity"          → inventories.quantity
     * - "Unit"              → inventories.unit
     * - "Total Cost RMB"    → inventories.price (dengan currency_id = 6 untuk RMB)
     * - "Supplier Name"     → inventories.supplier_lark
     * - "Destination"       → Filter: harus mengandung "BATAM"
     * - "Status"            → Filter: harus "Sent Out"
     *
     * CATATAN: Field name bisa berubah jika user rename field di Lark UI.
     * Jika ada perubahan nama field, mapping ini harus diupdate.
     *
     * Cara verify field name yang benar:
     * 1. Akses route: /inventory/lark-raw-data (super admin only)
     * 2. Lihat "fields" object untuk semua field name yang tersedia
     */
    protected const FIELD_MAPPING = [
        'inventories.name' => 'Item Requested',
        'inventories.project_lark' => 'Link Project',
        'inventories.quantity' => 'Quantity',
        'inventories.unit' => 'Unit',
        'inventories.price' => 'Cost Amount Per Unit',
        'inventories.supplier_lark' => 'Supplier Name',
        'inventories.destination' => 'Destination',
        'inventories.status' => 'Status',
        'inventories.img' => 'Item Photo',
    ];

    /**
     * Construct dari raw Lark record
     *
     * @param array $larkRecord Raw record dari Lark API response
     */
    public function __construct(array $larkRecord)
    {
        // Call parent untuk set recordId
        parent::__construct($larkRecord);

        // Extract fields berdasarkan mapping
        $fields = $larkRecord['fields'] ?? [];

        $this->nameRaw = $this->extractField($fields, 'inventories.name');
        $this->projectLarkRaw = $this->extractField($fields, 'inventories.project_lark');
        $this->quantityRaw = $this->extractField($fields, 'inventories.quantity');
        $this->unitRaw = $this->extractField($fields, 'inventories.unit');
        $this->totalCostRmbRaw = $this->extractField($fields, 'inventories.price');
        $this->supplierLarkRaw = $this->extractField($fields, 'inventories.supplier_lark');
        $this->destinationRaw = $this->extractField($fields, 'inventories.destination');
        $this->statusRaw = $this->extractField($fields, 'inventories.status');

        // Extract Item Photo - Lark returns as attachment array
        $itemPhotoFieldName = self::FIELD_MAPPING['inventories.img'] ?? null;
        $this->itemPhotoRaw = $itemPhotoFieldName ? $fields[$itemPhotoFieldName] ?? null : null;
    }

    /**
     * Check if record passes filter criteria
     *
     * Kondisi filter:
     * 1. Destination harus mengandung "BATAM" (case-insensitive)
     * 2. Status harus "Sent Out" (case-insensitive)
     *
     * @return bool
     */
    public function passesFilter(): bool
    {
        // Check destination mengandung "BATAM"
        $destinationValid = !empty($this->destinationRaw) && stripos($this->destinationRaw, 'BATAM') !== false;

        // Check status adalah "Sent Out"
        $statusValid = !empty($this->statusRaw) && strcasecmp(trim($this->statusRaw), 'Sent Out') === 0;

        return $destinationValid && $statusValid;
    }

    /**
     * Convert DTO to array (untuk debugging)
     */
    public function toArray(): array
    {
        return [
            'record_id' => $this->recordId,
            'name_raw' => $this->nameRaw,
            'project_lark_raw' => $this->projectLarkRaw,
            'quantity_raw' => $this->quantityRaw,
            'unit_raw' => $this->unitRaw,
            'total_cost_rmb_raw' => $this->totalCostRmbRaw,
            'supplier_lark_raw' => $this->supplierLarkRaw,
            'destination_raw' => $this->destinationRaw,
            'status_raw' => $this->statusRaw,
            'passes_filter' => $this->passesFilter(),
        ];
    }
}
