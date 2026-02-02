# LARK SYNC - Troubleshooting & FAQ

## ❓ Pertanyaan Umum

### 1. Kenapa error "Project name cannot be empty"?

**Root Cause:**

- Field mapping salah (field_id vs field_name mismatch)
- Field "Project Label" di Lark kosong/null
- extractField() tidak bisa parse tipe data dari Lark

**Solusi:**

```bash
# 1. Debug response API untuk lihat struktur data
php debug_lark_response.php

# 2. Cek field mapping di LarkProjectDTO
# Pastikan field_name sesuai dengan yang ada di API response
# Contoh: "Project Label" BUKAN "fld0e6YU25"

# 3. Check log Laravel
tail -f storage/logs/laravel.log | grep "Field value"
```

**Penjelasan:**
Lark API **TIDAK** support `field_key='id'` parameter di endpoint v1. API selalu mengembalikan **field_name** bukan **field_id**:

```json
// Yang dikembalikan API:
{
  "fields": {
    "Project Label": "Some Name",        // ✅ field_name
    "Sales / Ops IC": "John Doe"
  }
}

// BUKAN (field_id - tidak didukung):
{
  "fields": {
    "fld0e6YU25": "Some Name",          // ❌ Tidak ada
    "fld65MNtLk": "John Doe"
  }
}
```

---

### 2. Kenapa kolom "sales" dan "stage" tidak terisi?

**Penyebab:**

#### A. Field memang kosong di Lark Base

```bash
# Check di debug_lark_response.php:
Field Key: Sales / Ops IC
  Type: NULL
  Value:                  # ← Kosong di Lark!

Field Key: Batam Job Order Statuses
  Type: NULL
  Value:                  # ← Kosong di Lark!
```

**Solusi:** Ini **NORMAL**. Tidak semua project punya Sales IC atau Status. Database schema mengizinkan NULL.

#### B. Field mapping salah

```php
// SALAH - field tidak ada di response
'projects.sales' => 'Project List',

// BENAR - sesuai API response
'projects.sales' => 'Sales / Ops IC',
```

**Cara verify:**

```bash
# 1. Lihat field yang tersedia
php debug_lark_response.php | grep "Field Key:"

# 2. Cocokkan dengan FIELD_MAPPING di LarkProjectDTO.php
```

#### C. extractField() tidak bisa parse tipe data

Lark mengembalikan berbagai format data:

| Tipe Field    | Format Response           | Cara Parse       |
| ------------- | ------------------------- | ---------------- |
| Text          | `"value"` (string)        | Langsung ambil   |
| Number        | `123` (integer)           | Cast to string   |
| Single Select | `["option1"]` (array)     | Ambil index 0    |
| Multi Select  | `["opt1", "opt2"]`        | Join dengan `, ` |
| Relasi        | `[{"text_arr": ["val"]}]` | Ambil text_arr   |

**Solusi:** extractField() sudah handle semua tipe. Check log untuk tahu mana yang gagal:

```bash
tail -f storage/logs/laravel.log | grep "Field value tidak bisa di-parse"
```

---

### 3. Apakah parsing selalu UPDATE data atau DELETE lalu ADD baru?

**Jawaban: UPDATE (upsert), BUKAN delete+add**

#### Cara Kerja `updateOrCreate()`

```php
Project::updateOrCreate(
    ['lark_record_id' => $dto->recordId],  // ← WHERE clause
    $data                                   // ← Data untuk update/insert
);
```

**Behavior:**

1. **Jika record SUDAH ADA** (lark_record_id sudah ada di DB):
    - ✅ UPDATE semua field dengan data baru
    - ✅ `id` tetap sama (tidak ganti primary key)
    - ✅ `created_at` tetap (tidak berubah)
    - ✅ `updated_at` diupdate otomatis
    - ✅ `wasRecentlyCreated` = `false`

2. **Jika record BELUM ADA** (lark_record_id baru):
    - ✅ INSERT record baru
    - ✅ `id` auto increment
    - ✅ `created_at` dan `updated_at` di-set
    - ✅ `wasRecentlyCreated` = `true`

#### Contoh Real-World:

```php
// Sync pertama kali:
// Record "recv9ziBqi5vEt" belum ada → INSERT
// id: 1134, name: "Changkat Tampines", created_at: "2026-01-30 10:00:00"

// Sync kedua (data berubah di Lark):
// Record "recv9ziBqi5vEt" sudah ada → UPDATE
// id: 1134 (SAMA!), name: "Changkat Tampines v2", updated_at: "2026-01-30 11:00:00"
// created_at: "2026-01-30 10:00:00" ← TETAP SAMA
```

#### Keuntungan `updateOrCreate`:

- ✅ **Tidak ada data loss**: Relasi ke table lain tetap intact
- ✅ **ID stabil**: Foreign key di table lain tidak rusak
- ✅ **Audit trail**: `created_at` original tetap tersimpan
- ✅ **Atomic**: Update dalam 1 query (cepat & aman)

#### Soft Delete untuk Record yang Dihapus

Jika record dihapus dari Lark View, sync akan **soft delete** (bukan hard delete):

```php
// Di LarkProjectSyncService.php:
Project::whereNotNull('lark_record_id')
    ->whereNotIn('lark_record_id', $larkRecordIds)  // ← Tidak ada di Lark lagi
    ->whereNull('deleted_at')
    ->update(['deleted_at' => now()]);  // ← Soft delete
```

**Keuntungan soft delete:**

- ✅ Data historical tetap ada
- ✅ Bisa restore jika salah hapus
- ✅ Audit trail lengkap

---

### 4. Bagaimana cara parsing table Lark lain tanpa buat file baru?

**Jawaban:** Buat **Base Class yang reusable**

Saya sudah buatkan base class generic di:

- `app/DTO/BaseLarkDTO.php`
- `app/Services/Lark/BaseLarkSyncService.php`

#### Cara Pakai untuk Table Baru:

**Contoh: Sync table "Procurement" dari Lark**

##### 1. Buat DTO (Data Transfer Object):

```php
// app/DTO/LarkProcurementDTO.php
<?php

namespace App\DTO;

class LarkProcurementDTO extends BaseLarkDTO
{
    public readonly ?string $itemNameRaw;
    public readonly ?string $vendorRaw;
    public readonly ?string $quantityRaw;

    // Override field mapping
    protected const FIELD_MAPPING = [
        'item_name' => 'Item Name',           // Field di Lark
        'vendor' => 'Vendor Name',
        'quantity' => 'Qty',
    ];

    public function __construct(array $larkRecord)
    {
        parent::__construct($larkRecord);

        $fields = $larkRecord['fields'] ?? [];

        $this->itemNameRaw = $this->extractField($fields, 'item_name');
        $this->vendorRaw = $this->extractField($fields, 'vendor');
        $this->quantityRaw = $this->extractField($fields, 'quantity');
    }
}
```

##### 2. Buat Transformer (Normalisasi Data):

```php
// app/Transformers/ProcurementTransformer.php
<?php

namespace App\Transformers;

use App\DTO\LarkProcurementDTO;

class ProcurementTransformer
{
    public function transform(LarkProcurementDTO $dto): array
    {
        return [
            'lark_record_id' => $dto->recordId,
            'item_name' => trim($dto->itemNameRaw),
            'vendor' => $dto->vendorRaw,
            'quantity' => (int)$dto->quantityRaw,
            'created_by' => 'Sync from Lark',
            'last_sync_at' => now(),
        ];
    }
}
```

##### 3. Buat Sync Service:

```php
// app/Services/Lark/LarkProcurementSyncService.php
<?php

namespace App\Services\Lark;

use App\DTO\LarkProcurementDTO;
use App\Models\Procurement;
use App\Transformers\ProcurementTransformer;

class LarkProcurementSyncService extends BaseLarkSyncService
{
    protected string $baseId;
    protected string $tableId;
    protected ?string $viewId;

    public function __construct(
        LarkApiClient $apiClient,
        ProcurementTransformer $transformer
    ) {
        parent::__construct($apiClient);
        $this->transformer = $transformer;

        // Config dari .env
        $this->baseId = config('services.lark.procurement_base_id');
        $this->tableId = config('services.lark.procurement_table_id');
        $this->viewId = config('services.lark.procurement_view_id');
    }

    // Override abstract methods
    protected function getDtoClass(): string
    {
        return LarkProcurementDTO::class;
    }

    protected function getModelClass(): string
    {
        return Procurement::class;
    }

    protected function getUniqueKey(): string
    {
        return 'lark_record_id';
    }
}
```

##### 4. Tambah config di `.env`:

```env
# Procurement Lark Config
LARK_PROCUREMENT_BASE_ID=xxxxx
LARK_PROCUREMENT_TABLE_ID=yyyyy
LARK_PROCUREMENT_VIEW_ID=zzzzz
```

##### 5. Tambah route & controller:

```php
// routes/web.php
Route::post('/procurement/sync-from-lark', [ProcurementController::class, 'syncFromLark'])
    ->name('procurement.sync-from-lark');

// app/Http/Controllers/ProcurementController.php
public function syncFromLark()
{
    $syncService = app(LarkProcurementSyncService::class);
    $result = $syncService->sync();

    return redirect()->back()->with('success', "Synced {$result['created']} new, {$result['updated']} updated");
}
```

**SELESAI!** Tidak perlu copy-paste semua code, tinggal extend base class.

---

## 🛠️ Debugging Tools

### 1. Debug API Response Structure

```bash
php debug_lark_response.php
```

Output:

```
Field Key: Project Label
  Type: string
  Value: Changkat Tampines Tote Bags

Field Key: Sales / Ops IC
  Type: NULL
  Value:
```

### 2. Test Sync Manual

```bash
php test_sync.php
```

Output:

```
✅ SYNC BERHASIL!

Statistik:
- Fetched: 278
- Created: 1
- Updated: 276
- Errors: 1
```

### 3. Check Laravel Log

```bash
# Realtime monitoring
tail -f storage/logs/laravel.log

# Filter errors only
grep "ERROR" storage/logs/laravel.log

# Filter field extraction issues
grep "Field value" storage/logs/laravel.log
```

### 4. Inspect Database

```sql
-- Check synced projects
SELECT id, name, sales, stage, lark_record_id, created_by, last_sync_at
FROM projects
WHERE created_by = 'Sync from Lark'
ORDER BY last_sync_at DESC
LIMIT 10;

-- Check null fields
SELECT COUNT(*) as total_null_sales
FROM projects
WHERE created_by = 'Sync from Lark' AND sales IS NULL;

-- Check deactivated (soft deleted)
SELECT id, name, deleted_at
FROM projects
WHERE created_by = 'Sync from Lark' AND deleted_at IS NOT NULL;
```

---

## 🎯 Best Practices

### 1. Selalu Check Field Name Dulu

```bash
# Jangan asal tebak field name!
php debug_lark_response.php | grep "Field Key:"

# Cocokkan dengan FIELD_MAPPING di DTO
```

### 2. Handle NULL dengan Graceful

```php
// ✅ BENAR - allow null untuk optional fields
'sales' => $this->normalizeSales($dto->salesRaw), // bisa null

// ❌ SALAH - throw exception untuk optional field
if (empty($dto->salesRaw)) {
    throw new \Exception('Sales is required'); // ← Jangan!
}
```

### 3. Gunakan Logging untuk Debug

```php
// Di extractField() sudah ada logging:
\Log::debug('Field value is null', [
    'key' => $key,
    'field_name' => $fieldName,
    'record_id' => $this->recordId,
]);
```

### 4. Test Incremental

```bash
# Jangan langsung sync 1000 records
# Set limit dulu di API client untuk testing:

// LarkApiClient.php
$params = [
    'page_size' => 10, // ← Test dengan 10 records dulu
];
```

---

## 📊 Sync Statistics Interpretation

```
Fetched: 278    → Total records dari Lark API
Created: 1      → Record baru yang belum ada di DB
Updated: 276    → Record existing yang diupdate
Errors: 1       → Record yang gagal sync (null name)
Deactivated: 0  → Record yang dihapus dari Lark (soft delete)
```

**Analisa:**

- 278 total records dari view Lark
- 277 records berhasil sync (99.6% success rate)
- 1 error karena "Project Label" kosong di Lark (bukan bug code)
- Tidak ada record yang dihapus dari Lark

---

## 🚨 Common Errors & Solutions

| Error Message                      | Root Cause                                | Solution                               |
| ---------------------------------- | ----------------------------------------- | -------------------------------------- |
| "Project name cannot be empty"     | Field "Project Label" null di Lark        | Normal - record memang tidak lengkap   |
| "Field mapping not found"          | Key tidak ada di FIELD_MAPPING            | Tambahkan mapping di DTO               |
| "app secret invalid"               | LARK_APP_SECRET salah                     | Check .env & Lark developer console    |
| "Undefined array key 'fld0e6YU25'" | Pakai field_id tapi API return field_name | Ganti ke field_name di FIELD_MAPPING   |
| "Sales / Stage tidak terisi"       | Field memang NULL di Lark                 | Check di Lark Base apakah field kosong |

---

## 📞 Need Help?

1. **Check log Laravel first:**

    ```bash
    tail -50 storage/logs/laravel.log
    ```

2. **Run debug script:**

    ```bash
    php debug_lark_response.php | head -100
    ```

3. **Verify field mapping:**

    ```php
    // Uncomment di LarkProjectSyncService.php
    dd(LarkProjectDTO::getFieldMapping());
    ```

4. **Test dengan 1 record:**
    ```php
    // Di LarkApiClient, set page_size = 1
    $params = ['page_size' => 1];
    ```
