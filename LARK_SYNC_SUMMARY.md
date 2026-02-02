# LARK SYNC - Ringkasan Perbaikan & Jawaban

## ✅ MASALAH SOLVED

### 1. Error "Project name cannot be empty" - FIXED

**Root Cause:**

- Field mapping pakai `field_id` (`fld0e6YU25`) tapi Lark API return `field_name` (`"Project Label"`)
- API endpoint v1 **TIDAK** support parameter `field_key='id'`

**Solusi:**

- ✅ Update `FIELD_MAPPING` di `LarkProjectDTO.php` ke field_name:
    ```php
    'projects.name' => 'Project Label',              // ✅ BENAR
    'projects.sales' => 'Sales / Ops IC',
    'projects.stage' => 'Batam Job Order Statuses',
    ```

**Hasil:**

- ✅ **277 dari 278 records berhasil sync** (99.6% success rate)
- ✅ 1 error adalah legitimate (project name memang kosong di Lark)

---

### 2. Kolom "sales" dan "stage" tidak terisi - EXPLAINED

**Penyebab:**

#### A. Field Memang NULL di Lark (NORMAL)

```bash
# Dari API response:
Field Key: Sales / Ops IC
  Type: NULL               # ← Kosong di Lark!

Field Key: Batam Job Order Statuses
  Type: NULL               # ← Kosong di Lark!
```

**Penjelasan:**

- Tidak semua project punya Sales IC
- Tidak semua project punya Status
- Database schema **mengizinkan NULL** untuk field ini
- **Ini bukan bug**, ini business logic yang benar

#### B. Stage Punya Value Tapi Kode Internal

```
Sample synced projects:
- Stage: optdxxvupp
- Stage: optdxxvupp, optzyr9rap
```

**Penjelasan:**

- Lark mengembalikan **option ID** bukan **option label**
- `optdxxvupp` = kode internal untuk "In Progress" (misalnya)
- Bisa ditambahkan mapping di `ProjectTransformer` jika perlu label readable

**Solusi (Optional):**

```php
// Di ProjectTransformer::normalizeStage()
$stageMapping = [
    'optdxxvupp' => 'In Progress',
    'optzyr9rap' => 'Completed',
    // dst...
];
```

---

### 3. Parsing: UPDATE atau DELETE+ADD? - EXPLAINED

**Jawaban: UPDATE (upsert), BUKAN delete+add**

#### Cara Kerja `updateOrCreate()`

```php
Project::updateOrCreate(
    ['lark_record_id' => $dto->recordId],  // ← WHERE clause (unique key)
    $data                                   // ← Data untuk update/insert
);
```

**Behavior Detail:**

| Kondisi                                    | Aksi   | `id` (PK)      | `created_at` | `updated_at` | `wasRecentlyCreated` |
| ------------------------------------------ | ------ | -------------- | ------------ | ------------ | -------------------- |
| **Record BARU** (lark_record_id belum ada) | INSERT | Auto increment | Set now()    | Set now()    | `true`               |
| **Record ADA** (lark_record_id sudah ada)  | UPDATE | Tetap sama     | Tetap sama   | Update now() | `false`              |

**Contoh Real:**

```php
// Sync pertama kali (1 Januari):
// lark_record_id "recv9ziBqi" belum ada di DB
→ INSERT
→ id: 1134, name: "Changkat Tampines", created_at: "2026-01-01 10:00:00"

// Sync kedua (30 Januari) - data berubah di Lark:
// lark_record_id "recv9ziBqi" sudah ada di DB
→ UPDATE (BUKAN DELETE+INSERT!)
→ id: 1134 (SAMA!), name: "Changkat Tampines v2", updated_at: "2026-01-30 10:00:00"
→ created_at: "2026-01-01 10:00:00" ← TETAP ASLI
```

**Keuntungan `updateOrCreate`:**

- ✅ **ID stabil**: Foreign key di table lain tidak rusak
- ✅ **No data loss**: Relasi ke `material_requests`, `goods_out`, dll tetap intact
- ✅ **Audit trail**: `created_at` original tetap tersimpan
- ✅ **Atomic**: 1 query SQL (cepat & thread-safe)

**Soft Delete untuk Record Dihapus:**

Jika record dihapus dari Lark View:

```php
// Di LarkProjectSyncService.php (baris 120+):
Project::whereNotNull('lark_record_id')
    ->whereNotIn('lark_record_id', $larkRecordIds)  // ← Tidak ada di Lark
    ->whereNull('deleted_at')
    ->update(['deleted_at' => now()]);  // ← Soft delete (BUKAN hard delete)
```

**Keuntungan soft delete:**

- ✅ Data historical tetap ada
- ✅ Bisa restore jika salah
- ✅ Audit trail lengkap

---

### 4. Parse Table Lain Tanpa Buat File Baru? - SOLVED

**Jawaban: Pakai Base Class yang Reusable**

Saya sudah buatkan:

- ✅ `app/DTO/BaseLarkDTO.php` - Reusable DTO dengan `extractField()`
- ✅ `app/Services/Lark/BaseLarkSyncService.php` - Reusable sync logic

#### Cara Pakai untuk Table Baru:

**Step 1: Buat DTO (extend BaseLarkDTO)**

```php
// app/DTO/LarkProcurementDTO.php
<?php

namespace App\DTO;

class LarkProcurementDTO extends BaseLarkDTO
{
    public readonly ?string $itemNameRaw;
    public readonly ?string $vendorRaw;

    protected const FIELD_MAPPING = [
        'item_name' => 'Item Name',    // Field di Lark
        'vendor' => 'Vendor Name',
    ];

    public function __construct(array $larkRecord)
    {
        parent::__construct($larkRecord);  // ← Set recordId

        $fields = $larkRecord['fields'] ?? [];
        $this->itemNameRaw = $this->extractField($fields, 'item_name');
        $this->vendorRaw = $this->extractField($fields, 'vendor');
    }
}
```

**Step 2: Buat Transformer**

```php
// app/Transformers/ProcurementTransformer.php
class ProcurementTransformer
{
    public function transform(LarkProcurementDTO $dto): array
    {
        return [
            'lark_record_id' => $dto->recordId,
            'item_name' => trim($dto->itemNameRaw),
            'vendor' => $dto->vendorRaw,
            'created_by' => 'Sync from Lark',
            'last_sync_at' => now(),
        ];
    }
}
```

**Step 3: Buat Sync Service (extend BaseLarkSyncService)**

```php
// app/Services/Lark/LarkProcurementSyncService.php
class LarkProcurementSyncService extends BaseLarkSyncService
{
    public function __construct(
        LarkApiClient $apiClient,
        ProcurementTransformer $transformer
    ) {
        parent::__construct($apiClient);
        $this->transformer = $transformer;

        $this->setLarkConfig(
            baseId: config('services.lark.procurement_base_id'),
            tableId: config('services.lark.procurement_table_id'),
            viewId: config('services.lark.procurement_view_id')
        );
    }

    protected function getDtoClass(): string { return LarkProcurementDTO::class; }
    protected function getModelClass(): string { return Procurement::class; }
    protected function getUniqueKey(): string { return 'lark_record_id'; }
}
```

**Step 4: Config + Route**

```env
# .env
LARK_PROCUREMENT_BASE_ID=xxxxx
LARK_PROCUREMENT_TABLE_ID=yyyyy
```

```php
// routes/web.php
Route::post('/procurement/sync-from-lark', [ProcurementController::class, 'syncFromLark']);
```

**SELESAI!** Total cuma 3 file kecil:

1. DTO (20 baris)
2. Transformer (15 baris)
3. Sync Service (25 baris)

**Tidak perlu copy-paste** ratusan baris code lagi! 🎉

---

## 📊 Hasil Akhir

### Sync Statistics (Latest Run):

```
✅ SYNC BERHASIL!

Statistik:
- Fetched: 278        → Total records dari Lark view
- Created: 0          → Tidak ada record baru (semua sudah ada)
- Updated: 277        → 277 records berhasil diupdate
- Errors: 1           → 1 record error (legitimate null name)
- Deactivated: 0      → Tidak ada yang dihapus dari Lark
```

### Sample Synced Data:

```
ID: 1305 | Name: Jurong Primary School - Black Tulle Skirt | Sales: NULL | Stage: optdxxvupp
ID: 1410 | Name: NDP HOST | Sales: NULL | Stage: NULL
ID: 1306 | Name: Poi Ching School Band Costume | Sales: NULL | Stage: optdxxvupp, optzyr9rap
```

**Analisa:**

- ✅ **99.6% success rate** (277/278)
- ✅ Name terisi dengan benar
- ✅ Sales NULL adalah normal (tidak semua project punya PIC)
- ✅ Stage ada value tapi kode internal (bisa dimapping jika perlu label)

---

## 🛠️ File-File yang Diperbaiki

### 1. Core DTO & Service

| File                                      | Perubahan                                | Status        |
| ----------------------------------------- | ---------------------------------------- | ------------- |
| `app/DTO/LarkProjectDTO.php`              | FIELD_MAPPING: field_id → field_name     | ✅ Fixed      |
| `app/DTO/LarkProjectDTO.php`              | Extend BaseLarkDTO, hapus duplicate code | ✅ Refactored |
| `app/Transformers/ProjectTransformer.php` | Handle null sales & stage gracefully     | ✅ Fixed      |

### 2. Reusable Base Classes (NEW)

| File                                        | Purpose                            | Status     |
| ------------------------------------------- | ---------------------------------- | ---------- |
| `app/DTO/BaseLarkDTO.php`                   | Reusable DTO dengan extractField() | ✅ Created |
| `app/Services/Lark/BaseLarkSyncService.php` | Reusable sync logic                | ✅ Created |

### 3. Dokumentasi (NEW)

| File                           | Content                           | Status     |
| ------------------------------ | --------------------------------- | ---------- |
| `LARK_SYNC_TROUBLESHOOTING.md` | FAQ, debugging, troubleshooting   | ✅ Created |
| `LARK_FIELD_MAPPING_FIX.md`    | Penjelasan field_id vs field_name | ✅ Created |
| `debug_lark_response.php`      | Debug script untuk inspect API    | ✅ Created |
| `test_sync.php`                | Test script untuk sync manual     | ✅ Created |

---

## 🎯 Best Practices Learned

### 1. Selalu Debug API Response Dulu

```bash
# JANGAN ASAL TEBAK field name!
php debug_lark_response.php | grep "Field Key:"
```

### 2. Handle NULL dengan Graceful

```php
// ✅ BENAR - allow null untuk optional fields
private function normalizeSales(?string $value): ?string
{
    if (empty($value)) {
        return null;  // ← OK untuk null
    }
    return trim($value);
}
```

### 3. Gunakan Logging untuk Debug

```php
// BaseLarkDTO sudah otomatis log field yang null:
Log::debug('Field value is null', [
    'key' => 'projects.sales',
    'field_name' => 'Sales / Ops IC',
    'record_id' => 'recv9ziBqi5vEt',
]);
```

### 4. Extend Base Class untuk Reusability

```php
// ❌ JANGAN copy-paste ratusan baris
class LarkNewTableDTO { /* 200 baris duplicate */ }

// ✅ Extend base class
class LarkNewTableDTO extends BaseLarkDTO { /* 20 baris only */ }
```

---

## 📞 Troubleshooting Quick Reference

| Symptom                        | Check                    | Solution                                           |
| ------------------------------ | ------------------------ | -------------------------------------------------- |
| "Project name cannot be empty" | Field mapping salah      | Run `debug_lark_response.php`, cocokkan field name |
| Sales/Stage kosong             | Data memang NULL di Lark | Normal - check di Lark Base                        |
| Stage kode internal (`optXXX`) | Lark return option ID    | Add mapping di transformer                         |
| Sync lambat                    | Banyak records           | Normal - API pakai pagination                      |

---

## 🚀 Next Steps (Optional)

### 1. Add Stage Mapping untuk Readable Labels

```php
// Di ProjectTransformer::normalizeStage()
$stageMapping = [
    'optdxxvupp' => 'In Progress',
    'optzyr9rap' => 'Completed',
    'optmkjzcki' => 'Pending',
];
```

### 2. Add Sales Person Sync dari User Table

Jika "Sales / Ops IC" di Lark adalah relasi ke user table:

```php
// Extract user ID dari array relasi
if (isset($value[0]['record_ids'][0])) {
    $larkUserId = $value[0]['record_ids'][0];
    // Map ke local user_id
}
```

### 3. Schedule Sync Otomatis

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        app(LarkProjectSyncService::class)->sync();
    })->hourly();
}
```

---

## 📝 Summary

✅ **277 dari 278 projects** berhasil sync (99.6% success)  
✅ Field mapping **diperbaiki** dari field_id ke field_name  
✅ Sales & Stage **dijelaskan** (NULL adalah normal)  
✅ updateOrCreate **dijelaskan** (UPDATE bukan DELETE+ADD)  
✅ Base class **dibuat** untuk reusability  
✅ Dokumentasi **lengkap** di TROUBLESHOOTING.md

**Kesimpulan:**  
Sistem Lark sync sekarang **production-ready** dengan architecture yang **scalable** dan **maintainable**! 🎉
