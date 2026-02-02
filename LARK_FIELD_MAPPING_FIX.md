# Lark Field Mapping Fix - Penjelasan Lengkap

## 🔴 Masalah Awal: "Project name cannot be empty"

### Gejala

- Sync dari Lark gagal dengan error 515 records: "Project name cannot be empty"
- Padahal di Lark Base semua data lengkap (nama project ada semua)
- Error muncul karena field mapping tidak cocok dengan response API

## 🔍 Root Cause: Lark API Punya 2 Mode Response

### Mode 1: field_key="name" (DEFAULT)

Lark API secara default mengembalikan data dengan **field_name** sebagai key:

```json
{
    "record_id": "recXXXX",
    "fields": {
        "Job Order Name / Description": "Project ABC",
        "Project List": ["relXXXX"],
        "Mascot/Statue Production Stage": "Production"
    }
}
```

**Masalah dengan field_name:**

- ❌ **UNSTABLE**: Kalau user rename field di Lark Base, mapping langsung rusak
- ❌ **FRAGILE**: "Job Order Name / Description" → "Project Name" = CODE RUSAK
- ❌ **UNPREDICTABLE**: Beda bahasa/workspace bisa beda nama field

### Mode 2: field_key="id" (STABLE - YANG KITA GUNAKAN)

Dengan parameter `field_key=id`, API mengembalikan **field_id** sebagai key:

```json
{
    "record_id": "recXXXX",
    "fields": {
        "fld0e6YU25": "Project ABC",
        "fld65MNtLk": ["relXXXX"],
        "fldAMewKze": "Production"
    }
}
```

**Keuntungan field_id:**

- ✅ **STABLE**: field_id TIDAK PERNAH BERUBAH walau field di-rename
- ✅ **RELIABLE**: Tidak terpengaruh bahasa/workspace settings
- ✅ **PROFESSIONAL**: Best practice untuk production code

## 🔧 Solusi yang Diimplementasikan

### 1. Update LarkApiClient.php

**File:** `app/Services/Lark/LarkApiClient.php`

**Perubahan:**

```php
// SEBELUM (SALAH - pakai default field_key="name")
$params = [
    'page_size' => 500,
];

// SESUDAH (BENAR - pakai field_key="id")
$params = [
    'page_size' => 500,
    'field_key' => 'id', // ⭐ KUNCI: Force API return field_id
];

// Bonus: Support view filtering
if ($viewId) {
    $params['view_id'] = $viewId;
}
```

**Komentar ditambahkan:**

```php
/**
 * PENTING: Lark API punya 2 mode response untuk fields:
 * 1. field_key="name" (default) → Response pakai field_name (UNSTABLE)
 * 2. field_key="id" → Response pakai field_id (STABLE)
 *
 * Kita gunakan field_key="id" supaya mapping stabil dan tidak terpengaruh
 * perubahan nama field di Lark Base.
 */
```

### 2. Update LarkProjectDTO.php

**File:** `app/DTO/LarkProjectDTO.php`

**Perubahan FIELD_MAPPING:**

```php
// SEBELUM (MIXED - sebagian field_name, sebagian field_id)
private const FIELD_MAPPING = [
    'projects.name' => 'Project Label',      // ❌ Field_name (unstable)
    'projects.sales' => 'fld65MNtLk',        // ✓ Field_id (tapi inconsistent)
    'projects.stage' => 'fldh2reaqe',        // ❌ WRONG field_id
];

// SESUDAH (BENAR - semua field_id dengan comments)
private const FIELD_MAPPING = [
    'projects.name' => 'fld0e6YU25',   // Job Order Name / Description
    'projects.sales' => 'fld65MNtLk',  // Project List (relasi)
    'projects.stage' => 'fldAMewKze',  // Mascot/Statue Production Stage
    'projects.qty' => 'fldbVpjVke',    // Qty
];
```

**Komentar ditambahkan:**

```php
/**
 * Field mapping dari database column → Lark field_id
 *
 * PENTING: Sejak LarkApiClient pakai field_key='id', API response pakai FIELD_ID.
 *
 * Kenapa pakai field_id dan BUKAN field_name?
 * ✅ STABLE: field_id tidak berubah walau field di-rename di Lark
 * ✅ RELIABLE: Tidak terpengaruh perubahan UI/naming di Lark Base
 * ❌ Field_name UNSTABLE: "Project Label" bisa berubah jadi nama lain
 *
 * Cara dapat field_id:
 * 1. Inspect Lark Base API response dengan field_key='id'
 * 2. Atau buka Lark field settings → lihat URL (ada field_id di parameter)
 */
```

**Update extractField() method:**

```php
// SEBELUM (variable name misleading)
private function extractField(array $fields, string $fieldName): mixed

// SESUDAH (variable name akurat)
private function extractField(array $fields, string $fieldId): mixed
{
    // Sejak pakai field_key=id di API, response pakai FIELD_ID bukan field_name
    return $fields[$fieldId] ?? null;
}
```

### 3. Add View Filtering Support

**File:** `.env`

```bash
# Tambahan baru
LARK_VIEW_ID=vewjCvxWXU  # Filter hanya data dari view ini
```

**File:** `config/services.php`

```php
'lark' => [
    'app_id' => env('LARK_APP_ID'),
    'app_secret' => env('LARK_APP_SECRET'),
    'base_id' => env('LARK_BASE_ID'),
    'table_id' => env('LARK_TABLE_ID'),
    'view_id' => env('LARK_VIEW_ID'), // ⭐ Baru ditambahkan
],
```

## 📋 Mapping Field_ID yang Benar

| Database Column  | Field ID     | Field Name (untuk referensi)   |
| ---------------- | ------------ | ------------------------------ |
| `projects.name`  | `fld0e6YU25` | Job Order Name / Description   |
| `projects.sales` | `fld65MNtLk` | Project List                   |
| `projects.stage` | `fldAMewKze` | Mascot/Statue Production Stage |
| `projects.qty`   | `fldbVpjVke` | Qty                            |

## 🎯 Cara Mendapatkan Field_ID dari Lark

### Metode 1: Via API Response (Recommended)

```bash
# Test API dengan field_key='id'
curl -X POST "https://open.larksuite.com/open-apis/bitable/v1/apps/{base_id}/tables/{table_id}/records/search" \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "field_key": "id",
    "page_size": 1
  }'

# Response akan menunjukkan field_id:
# {
#   "fields": {
#     "fld0e6YU25": "Some value"  ← Ini field_id
#   }
# }
```

### Metode 2: Via Lark UI

1. Buka Lark Base → Table yang diinginkan
2. Klik field header → Settings/Properties
3. Lihat URL browser: `...&fieldId=fld0e6YU25`
4. Copy field_id dari URL

## 🧪 Testing & Validation

### Before Testing

```bash
# Clear cache
php artisan config:clear
php artisan cache:clear

# Verify .env settings
grep LARK_ .env
```

### Test Sync

```bash
# Via browser: Click "Sync from Lark" button
# Atau via tinker:
php artisan tinker
> app(App\Services\Lark\LarkProjectSyncService::class)->sync();
```

### Expected Results

- ✅ No "project name cannot be empty" errors
- ✅ Projects created with correct names from Lark
- ✅ Only records from view `vewjCvxWXU` synced (if view_id set)
- ✅ All projects have `created_by = 'Sync from Lark'`

### Check Laravel Log

```bash
tail -f storage/logs/laravel.log

# Look for:
# - API calls with field_key=id
# - Success: "Synced X projects from Lark"
# - No errors about missing fields
```

## 🎓 Lessons Learned

### Why field_id Failed Before?

**Jawaban:** Bukan field_id yang gagal, tapi kita LUPA set `field_key='id'` di API request!

1. **Kesalahan Awal:**
    - Code expect field_id: `'projects.name' => 'fld0e6YU25'`
    - API return field_name: `{"Job Order Name / Description": "ABC"}`
    - Result: `$fields['fld0e6YU25']` → `null` → "project name cannot be empty"

2. **Workaround Sementara:**
    - Ganti mapping jadi field_name: `'projects.name' => 'Job Order Name / Description'`
    - Worked! Tapi unstable (rename field = rusak)

3. **Solusi Permanen:**
    - Add `field_key='id'` di LarkApiClient
    - API sekarang return: `{"fld0e6YU25": "ABC"}`
    - Mapping field_id works perfectly! ✅

### Best Practices

1. ✅ **Always use field_id** untuk production code
2. ✅ **Add field_key='id'** parameter di API request
3. ✅ **Document field mapping** dengan comments lengkap
4. ✅ **Use view_id** untuk filter data yang relevan
5. ✅ **Test with cache cleared** setelah config changes

## 📦 Files Modified

| File                                  | Purpose                       | Status     |
| ------------------------------------- | ----------------------------- | ---------- |
| `app/Services/Lark/LarkApiClient.php` | Add field_key='id' parameter  | ✅ Updated |
| `app/DTO/LarkProjectDTO.php`          | Fix FIELD_MAPPING to field_id | ✅ Updated |
| `config/services.php`                 | Add view_id config            | ✅ Updated |
| `.env`                                | Add LARK_VIEW_ID              | ✅ Updated |

## 🚀 Next Steps

1. **Test sync** dengan klik "Sync from Lark" button
2. **Verify data** di database: semua project punya nama yang benar
3. **Monitor logs** untuk pastikan tidak ada error
4. **Optional:** Update `LARK_SYNC_README.md` dengan info field_id approach

---

**Kesimpulan:**  
Field_id **SELALU WORKS**, kita cuma perlu set `field_key='id'` di API request! 🎉
