<?php

namespace App\DTO;

use Illuminate\Support\Facades\Log;

/**
 * Base Lark DTO
 *
 * Abstract class untuk semua Lark DTO. Menyediakan:
 * - extractField() method yang reusable
 * - recordId property yang wajib
 * - Logging untuk debugging
 *
 * Cara pakai:
 * 1. Extend class ini
 * 2. Define FIELD_MAPPING constant
 * 3. Override constructor untuk extract fields sesuai kebutuhan
 */
abstract class BaseLarkDTO
{
    public readonly string $recordId;

    /**
     * Field mapping dari key internal → Lark field name
     *
     * Contoh:
     * protected const FIELD_MAPPING = [
     *     'name' => 'Project Label',
     *     'sales' => 'Sales / Ops IC',
     * ];
     *
     * @var array<string, string>
     */
    protected const FIELD_MAPPING = [];

    /**
     * Construct base - extract record_id
     *
     * @param array $larkRecord Raw record dari Lark API
     */
    public function __construct(array $larkRecord)
    {
        $this->recordId = $larkRecord['record_id'] ?? throw new \InvalidArgumentException('record_id is required in Lark record');
    }

    /**
     * Extract field dari Lark response menggunakan field mapping
     *
     * Method ini handle berbagai tipe data yang dikembalikan Lark API:
     * - String langsung
     * - Number (integer/float)
     * - Array of strings (multi-select)
     * - Array dengan metadata [{"text": "value"}]
     * - Array relasi [{"text_arr": ["val1", "val2"]}]
     * - Person fields [{"id": "...", "name": "...", "email": "..."}]
     * - Attachment fields [{"url": "...", "tmp_url": "...", "file_token": "..."}]
     *
     * @param array $fields Fields array dari Lark record
     * @param string $key Key dari FIELD_MAPPING
     * @return string|null Extracted value atau null jika tidak ada
     */
    protected function extractField(array $fields, string $key): ?string
    {
        $fieldName = static::FIELD_MAPPING[$key] ?? null;

        if (!$fieldName) {
            Log::warning('Field mapping not found', [
                'dto_class' => static::class,
                'key' => $key,
                'available_keys' => array_keys(static::FIELD_MAPPING),
            ]);
            return null;
        }

        $value = $fields[$fieldName] ?? null;

        // Log untuk debugging field kosong
        if ($value === null) {
            Log::debug('Field value is null', [
                'dto_class' => static::class,
                'key' => $key,
                'field_name' => $fieldName,
                'record_id' => $this->recordId,
            ]);
            return null;
        }

        // Handle berbagai tipe data dari Lark:

        // 1. String langsung (paling umum)
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        // 2. Number
        if (is_numeric($value)) {
            return (string) $value;
        }

        // 3. Array - handle berbagai struktur
        if (is_array($value) && !empty($value)) {
            // 3a. Person field: [{"id": "ou_xxx", "name": "John Doe", "email": "..."}]
            // Extract names dari semua person objects
            if (isset($value[0]['name']) && isset($value[0]['id'])) {
                $names = array_filter(
                    array_map(function ($person) {
                        return is_string($person['name'] ?? null) ? trim($person['name']) : null;
                    }, $value),
                );

                if (!empty($names)) {
                    return implode(', ', $names);
                }
            }

            // 3b. Attachment field: [{"url": "...", "file_token": "...", "tmp_url": "..."}]
            // Extract URLs (prefer 'url' over 'tmp_url')
            if (isset($value[0]['url']) || isset($value[0]['tmp_url'])) {
                $urls = array_filter(
                    array_map(function ($attachment) {
                        return $attachment['url'] ?? ($attachment['tmp_url'] ?? null);
                    }, $value),
                );

                if (!empty($urls)) {
                    return implode(', ', $urls);
                }
            }

            // 3c. Simple array of strings: ["value1", "value2"]
            if (isset($value[0]) && is_string($value[0])) {
                $filtered = array_filter($value, function ($v) {
                    return is_string($v) && trim($v) !== '';
                });
                if (!empty($filtered)) {
                    return implode(', ', $filtered);
                }
            }

            // 3d. Array dengan struktur [{"text": "value"}] (tipe Text dengan metadata)
            if (isset($value[0]['text'])) {
                $text = $value[0]['text'];
                if (!empty($text) && is_string($text)) {
                    return trim($text);
                }
            }

            // 3e. Array dengan struktur [{"record_ids": [...], "text_arr": [...]}] (relasi)
            if (isset($value[0]['text_arr']) && is_array($value[0]['text_arr'])) {
                $textArr = array_filter($value[0]['text_arr'], function ($v) {
                    return is_string($v) && trim($v) !== '';
                });
                if (!empty($textArr)) {
                    return implode(', ', $textArr);
                }
            }
        }

        Log::debug('Field value tidak bisa di-parse', [
            'dto_class' => static::class,
            'key' => $key,
            'field_name' => $fieldName,
            'value_type' => gettype($value),
            'value_sample' => is_array($value) ? json_encode(array_slice($value, 0, 2)) : substr((string) $value, 0, 100),
        ]);

        return null;
    }

    /**
     * Get field mapping untuk debugging
     *
     * @return array<string, string>
     */
    public static function getFieldMapping(): array
    {
        return static::FIELD_MAPPING;
    }
}
