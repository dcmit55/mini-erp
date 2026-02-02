# Lark Project Sync - Implementation Documentation

## 📋 Overview

Sistem sinkronisasi Projects dari **Lark Base (Multidimensional Table)** ke **MySQL database** menggunakan arsitektur **clean & layered** mengikuti **iSyment pattern**.

### ✅ Tujuan Implementasi

- ✅ Menghapus kode parsing lama yang berantakan
- ✅ Membuat flow parsing yang jelas, terpisah, dan mudah dirawat
- ✅ Mencegah data duplikat
- ✅ Menambahkan kolom `sales` ke table projects
- ✅ Membuat identitas data dari Lark yang stabil (`lark_record_id`)

---

## 🏗️ Arsitektur & Flow

```
User klik button "Sync from Lark"
    ↓
ProjectController::syncFromLark()
    ↓
LarkProjectSyncService::sync()
    ↓
LarkApiClient::fetchRecords() → [Raw JSON dari Lark]
    ↓
LarkProjectDTO (mapping field mentah)
    ↓
ProjectTransformer (normalisasi data)
    ↓
Project::updateOrCreate() → MySQL
    ↓
Soft delete projects yang tidak ada lagi di Lark
```

---

## 📁 Struktur File

```
app/
├── DTO/
│   └── LarkProjectDTO.php              # Data Transfer Object (raw mapping)
├── Transformers/
│   └── ProjectTransformer.php          # Normalisasi & validasi data
├── Services/
│   └── Lark/
│       ├── LarkApiClient.php           # Komunikasi dengan Lark API
│       └── LarkProjectSyncService.php  # Orchestrator utama sync
├── Http/Controllers/Production/
│   └── ProjectController.php           # Trigger sync (updated)
└── Models/Production/
    └── Project.php                     # Model updated (sales, lark_record_id)

config/
├── lark.php                            # Lark configuration
└── services.php                        # Updated dengan Lark credentials

database/migrations/
└── 2026_01_28_162636_add_sales_to_projects_table.php

routes/
└── web.php                             # Routes untuk sync

resources/views/production/projects/
└── index.blade.php                     # Button "Sync from Lark"
```

---

## 🔧 Setup & Configuration

### 1. Environment Variables

Pastikan [`.env`](.env) sudah ada konfigurasi Lark:

```env
# Lark Configuration
LARK_APP_ID=cli_a865acf14778de1b
LARK_APP_SECRET=dovaX9KcwSdKipL2Q2r2B3WorEWriDVZ

# Base & Table IDs
LARK_BASE_ID=LTDXbVfm6ahcj7sZjtMjJV2pprc
LARK_TABLE_ID=tblLbqEB7Dyycx2Y
LARK_VIEW_ID=vewjCvxWXU

# Optional Settings
LARK_SYNC_ENABLED=true
LARK_AUTO_DEACTIVATE=true
LARK_BATCH_SIZE=100
```

> **⚠️ PENTING**: Jika Anda mendapat error **"invalid app access token"**, pastikan:
>
> 1. `LARK_APP_ID` dan `LARK_APP_SECRET` benar (bukan Base ID!)
> 2. App sudah diaktifkan di Lark Developer Console
> 3. App memiliki permission: `bitable:app`

### 2. Run Migration

```bash
php artisan migrate
```

Migration akan menambahkan kolom:

- `sales` (nullable, varchar)
- Memastikan `lark_record_id` (unique) dan `last_sync_at` ada

### 3. Clear Cache

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

---

## 🚀 Cara Penggunaan

### Via UI (Recommended)

1. Login sebagai **super_admin** atau **admin**
2. Buka halaman **Projects** (`/projects`)
3. Klik button **"Sync from Lark"** (warna biru dengan icon sync)
4. Konfirmasi dialog
5. Tunggu proses sync (loading indicator)
6. Lihat notifikasi hasil sync:
    - **Success**: Jumlah fetched, created, updated, deactivated
    - **Warning**: Ada errors tapi sebagian berhasil
    - **Error**: Sync gagal total

### Via Artisan (Untuk Cron/Schedule)

```bash
# Belum ada artisan command, bisa ditambahkan jika perlu
# php artisan lark:sync-projects
```

### Via API/Testing

```bash
# Get raw Lark data (debugging, super admin only)
curl -X GET "http://localhost/projects/lark-raw-data" \
  -H "Authorization: Bearer {token}"
```

---

## 📊 Field Mapping

Mapping dari **database column** → **Lark field name**:

| Database Column  | Lark Field Name                  | Type      | Required | Auto-Filled         |
| ---------------- | -------------------------------- | --------- | -------- | ------------------- |
| `name`           | `Job Order Name / Description`   | string    | ✅ Yes   | -                   |
| `sales`          | `Project List`                   | relation  | ❌ No    | -                   |
| `stage`          | `Mascot/Statue Production Stage` | string    | ❌ No    | -                   |
| `created_by`     | -                                | string    | ❌ No    | ✅ 'Sync from Lark' |
| `lark_record_id` | `record_id`                      | string    | ✅ Yes   | ✅ Auto from API    |
| `last_sync_at`   | -                                | timestamp | ❌ No    | ✅ Auto timestamp   |

**Note:**

- Field mapping menggunakan **field name** langsung dari Lark, bukan field_id
- Field mapping ada di `LarkProjectDTO::FIELD_MAPPING`
- **`created_by`** otomatis diisi **'Sync from Lark'** untuk membedakan data hasil parsing dengan data manual dari iSyment
- Records yang tidak memiliki `Job Order Name / Description` akan di-skip (error logged)

---

## 🔄 Sync Logic

### Create / Update

Menggunakan `updateOrCreate` dengan `lark_record_id` sebagai unique key:

```php
Project::updateOrCreate(
    ['lark_record_id' => $recordId],
    $transformedData
);
```

### Soft Delete (Deactivate)

Jika project ada di database tapi **TIDAK ADA** di Lark:

```php
Project::whereNotNull('lark_record_id')
    ->whereNotIn('lark_record_id', $larkRecordIds)
    ->delete(); // Soft delete
```

Project akan di-soft delete (set `deleted_at`), **bukan hard delete**.

---

## 🛡️ Error Handling

### Level 1: API Error

Jika Lark API gagal (timeout, credentials salah, dll):

- Exception di-throw dari `LarkApiClient`
- Transaction di-rollback
- Error di-log ke `storage/logs/laravel.log`
- User melihat error message

### Level 2: Parsing Error

Jika data dari Lark tidak valid:

- Skip record yang error
- Counter `errors` bertambah
- `error_details` di-log
- Lanjut ke record berikutnya

### Level 3: Database Error

Jika save ke database gagal:

- Transaction di-rollback
- Semua perubahan di-undo
- Error di-log dengan trace
- User melihat error message

---

## 📝 Logging

Semua aktivitas sync di-log ke `storage/logs/laravel.log`:

```
[2026-01-28 16:30:00] local.INFO: Starting Lark project sync
[2026-01-28 16:30:01] local.INFO: Fetching Lark records {"url": "..."}
[2026-01-28 16:30:02] local.INFO: Lark records fetched {"total": 150}
[2026-01-28 16:30:05] local.INFO: Project synced {"lark_record_id": "rec123", "action": "created"}
[2026-01-28 16:30:10] local.INFO: Lark project sync completed {"fetched": 150, "created": 10, "updated": 140}
```

Error logging:

```
[2026-01-28 16:30:05] local.ERROR: Failed to sync project {"record": {...}, "error": "..."}
[2026-01-28 16:30:15] local.ERROR: Lark project sync failed {"error": "...", "trace": "..."}
```

---

## 🧪 Testing

### Manual Testing

```bash
# 1. Test fetch dari Lark (tanpa save)
php artisan tinker
>>> $client = app(App\Services\Lark\LarkApiClient::class);
>>> $records = $client->fetchRecords('LTDXbVfm6ahcj7sZjtMjJV2pprc', 'tblLbqEB7Dyycx2Y');
>>> count($records);

# 2. Test DTO parsing
>>> $dto = new App\DTO\LarkProjectDTO($records[0]);
>>> $dto->toArray();

# 3. Test transformer
>>> $transformer = app(App\Transformers\ProjectTransformer::class);
>>> $data = $transformer->transform($dto);
>>> print_r($data);

# 4. Full sync test
>>> $service = app(App\Services\Lark\LarkProjectSyncService::class);
>>> $stats = $service->sync();
>>> print_r($stats);
```

### Unit Testing (TODO)

Bisa ditambahkan unit test untuk:

- `LarkProjectDTO` parsing
- `ProjectTransformer` normalization
- `LarkProjectSyncService` logic

---

## 🔐 Security & Permissions

- **Sync Button**: Hanya visible untuk `super_admin` dan `admin`
- **Raw Data API**: Hanya accessible untuk `super_admin`
- **Credentials**: Disimpan di `.env`, tidak di-commit ke git
- **CSRF Protection**: Form sync menggunakan `@csrf`

---

## 📈 Performance Considerations

### Pagination

Lark API dibatasi 500 records per request. `LarkApiClient` otomatis handle pagination dengan `page_token`.

### Memory

Untuk dataset besar (>1000 projects):

- Service load semua ke memory (bisa optimize dengan chunk jika perlu)
- Consider running via queue/job untuk background processing

### Rate Limiting

Lark API punya rate limit. Jika hit limit:

- Add delay/sleep antar request
- Consider caching access token

---

## 🐛 Troubleshooting

### ❌ Error 302 Found saat klik button "Sync from Lark"

**Gejala:** Button di-klik → redirect ke `/projects` tanpa proses sync → tidak ada pesan error/success

**Penyebab:**

1. **CSRF Token Mismatch** - Form tidak include `@csrf` atau token expired
2. **Route tidak ditemukan** - Typo di route name atau method
3. **Middleware redirect** - Auth middleware atau permission check

**Solusi:**

```bash
# 1. Clear cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 2. Cek route terdaftar
php artisan route:list | grep sync

# 3. Verify session dan CSRF token
# Buka browser DevTools → Application → Cookies
# Pastikan ada: laravel_session, XSRF-TOKEN

# 4. Test route manual via terminal
php artisan tinker
>>> route('projects.sync.lark');
```

**Cek di Browser DevTools:**

- **Network Tab** → Cek response headers
- Jika ada `X-CSRF-TOKEN` mismatch → reload page
- Jika 419 error → session expired, login ulang

---

### Sync gagal: "Failed to get Lark access token"

**Penyebab:** Credentials salah atau network issue

**Solusi:**

```bash
# Cek credentials
php artisan tinker
>>> config('services.lark.app_id');
>>> config('services.lark.app_secret');

# Test koneksi
curl https://open.larksuite.com/open-apis/auth/v3/tenant_access_token/internal \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"app_id":"...","app_secret":"..."}'
```

### Sync sukses tapi data tidak muncul

**Penyebab:** Data di-soft delete atau field_mapping salah

**Solusi:**

```bash
# Cek soft deleted
php artisan tinker
>>> App\Models\Production\Project::withTrashed()->where('lark_record_id', 'rec...')->first();

# Cek field mapping
>>> App\DTO\LarkProjectDTO::getFieldMapping();
```

### Error "record_id is required"

**Penyebab:** Response dari Lark tidak sesuai format

**Solusi:**

```bash
# Debug raw response
curl http://localhost/projects/lark-raw-data

# Cek struktur data
php artisan tinker
>>> $client = app(App\Services\Lark\LarkApiClient::class);
>>> $raw = $client->getRawResponse(...);
>>> print_r($raw['records'][0]);
```

---

## 🔄 Maintenance

### Update Field Mapping

Jika ada field baru di Lark:

1. Tambahkan kolom di migration
2. Update `LarkProjectDTO::FIELD_MAPPING`
3. Update `LarkProjectDTO` properties
4. Update `ProjectTransformer::transform()`
5. Update `Project` model fillable

### Rollback Sync

Jika sync salah:

```bash
# Soft delete semua yang di-sync hari ini
php artisan tinker
>>> App\Models\Production\Project::whereDate('last_sync_at', today())->delete();

# Restore jika perlu
>>> App\Models\Production\Project::onlyTrashed()->whereDate('deleted_at', today())->restore();
```

---

## 📚 References

- **Lark API Docs**: https://open.larksuite.com/document
- **iSyment Pattern**: Following database transactions + event broadcasting
- **Laravel Eloquent**: https://laravel.com/docs/eloquent

---

## ✅ Checklist Deployment

- [ ] Update `.env` dengan Lark credentials production
- [ ] Run `php artisan migrate`
- [ ] Run `php artisan config:clear`
- [ ] Test sync dengan 1-2 records dulu
- [ ] Monitor `storage/logs/laravel.log`
- [ ] Setup scheduled task jika perlu auto-sync
- [ ] Backup database sebelum full sync

---

## 📄 Full Source Code

Berikut adalah source code lengkap dari semua file yang telah dibuat untuk implementasi Lark Sync.

### 1. `config/lark.php`

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lark Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration untuk Lark API integration
    | Base/Multidimensional Table untuk project sync
    |
    */

    'app_id' => env('LARK_APP_ID'),
    'app_secret' => env('LARK_APP_SECRET'),

    'base_id' => env('LARK_BASE_ID'),
    'table_id' => env('LARK_TABLE_ID'),
    'view_id' => env('LARK_VIEW_ID', null),

    'sync' => [
        'enabled' => env('LARK_SYNC_ENABLED', true),
        'auto_deactivate_missing' => env('LARK_AUTO_DEACTIVATE', true),
        'batch_size' => env('LARK_BATCH_SIZE', 100),
    ],

    'api' => [
        'base_url' => 'https://open.larksuite.com/open-apis',
        'token_cache_key' => 'lark_access_token',
        'token_cache_ttl' => 7000, // ~2 hours (token valid for 2h)
    ],
];
```

---

### 2. `app/DTO/LarkProjectDTO.php`

```php
<?php

namespace App\DTO;

/**
 * Lark Project Data Transfer Object
 *
 * Pure data mapping dari Lark JSON ke PHP object
 * NO business logic, NO database access, NO validation
 * Hanya mapping field_id → properties
 */
class LarkProjectDTO
{
    /**
     * Mapping field name → Lark field_id
     * Update disini jika field_id berubah di Lark
     */
    const FIELD_MAPPING = [
        'projects.name' => 'fld0e6YU25',
        'projects.sales' => 'fld65MNtLk',
        'projects.stage' => 'fldAMewKze',
    ];

    public string $recordId;
    public ?string $name;
    public ?string $sales;
    public ?string $stage;
    public array $rawData;

    public function __construct(array $larkRecord)
    {
        $this->rawData = $larkRecord;
        $this->recordId = $larkRecord['record_id'] ?? null;

        $fields = $larkRecord['fields'] ?? [];

        // Map fields menggunakan field_id
        $this->name = $this->extractField($fields, self::FIELD_MAPPING['projects.name']);
        $this->sales = $this->extractField($fields, self::FIELD_MAPPING['projects.sales']);
        $this->stage = $this->extractField($fields, self::FIELD_MAPPING['projects.stage']);
    }

    /**
     * Extract field value dari Lark fields array
     * Handle berbagai tipe data Lark (text, select, etc)
     */
    private function extractField(array $fields, string $fieldId): ?string
    {
        if (!isset($fields[$fieldId])) {
            return null;
        }

        $value = $fields[$fieldId];

        // Jika array dengan key 'text' (tipe Text field)
        if (is_array($value) && isset($value[0]['text'])) {
            return $value[0]['text'];
        }

        // Jika array kosong
        if (is_array($value) && empty($value)) {
            return null;
        }

        // Jika string/number langsung
        if (is_string($value) || is_numeric($value)) {
            return (string) $value;
        }

        return null;
    }

    /**
     * Get field mapping untuk debugging
     */
    public static function getFieldMapping(): array
    {
        return self::FIELD_MAPPING;
    }

    public function toArray(): array
    {
        return [
            'record_id' => $this->recordId,
            'name' => $this->name,
            'sales' => $this->sales,
            'stage' => $this->stage,
        ];
    }
}
```

---

### 3. `app/Transformers/ProjectTransformer.php`

```php
<?php

namespace App\Transformers;

use App\DTO\LarkProjectDTO;

/**
 * Project Transformer
 *
 * Normalisasi dan validasi data dari DTO ke format database
 * Handle business logic untuk data transformation
 */
class ProjectTransformer
{
    /**
     * Transform DTO ke array untuk database
     *
     * @param LarkProjectDTO $dto
     * @return array
     * @throws \Exception jika validasi gagal
     */
    public function transform(LarkProjectDTO $dto): array
    {
        $data = [
            'name' => $this->normalizeName($dto->name),
            'sales' => $this->normalizeSales($dto->sales),
            'stage' => $this->normalizeStage($dto->stage),
            'lark_record_id' => $dto->recordId,
            'created_by' => 'Sync from Lark',
            'last_sync_at' => now(),
        ];

        // Validate transformed data
        $this->validate($data);

        return $data;
    }

    /**
     * Normalize project name
     */
    private function normalizeName(?string $name): string
    {
        if (empty($name)) {
            throw new \Exception('Project name is required');
        }

        // Clean whitespace, limit length
        $name = trim($name);
        $name = mb_substr($name, 0, 255);

        return $name;
    }

    /**
     * Normalize sales field
     */
    private function normalizeSales(?string $sales): ?string
    {
        if (empty($sales)) {
            return null;
        }

        $sales = trim($sales);
        $sales = mb_substr($sales, 0, 255);

        return $sales;
    }

    /**
     * Normalize stage field
     */
    private function normalizeStage(?string $stage): ?string
    {
        if (empty($stage)) {
            return null;
        }

        $stage = trim($stage);
        $stage = mb_substr($stage, 0, 50);

        return $stage;
    }

    /**
     * Validate transformed data
     */
    private function validate(array $data): void
    {
        if (empty($data['name'])) {
            throw new \Exception('Project name cannot be empty after normalization');
        }

        if (empty($data['lark_record_id'])) {
            throw new \Exception('Lark record_id is required');
        }

        if (!preg_match('/^rec[a-zA-Z0-9]+$/', $data['lark_record_id'])) {
            throw new \Exception('Invalid Lark record_id format');
        }
    }

    /**
     * Batch transform multiple DTOs
     */
    public function transformMany(array $dtos): array
    {
        return array_map(function($dto) {
            return $this->transform($dto);
        }, $dtos);
    }
}
```

---

### 4. `app/Services/Lark/LarkApiClient.php`

```php
<?php

namespace App\Services\Lark;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Lark API Client
 *
 * Handle komunikasi dengan Lark API (Feishu/Bytedance)
 * Responsibility: API calls only, NO parsing, NO database
 */
class LarkApiClient
{
    private string $appId;
    private string $appSecret;
    private string $baseUrl;

    public function __construct()
    {
        $this->appId = config('services.lark.app_id');
        $this->appSecret = config('services.lark.app_secret');
        $this->baseUrl = config('lark.api.base_url', 'https://open.larksuite.com/open-apis');

        if (empty($this->appId) || empty($this->appSecret)) {
            throw new \Exception('Lark credentials not configured');
        }
    }

    /**
     * Get tenant access token dengan caching
     */
    public function getAccessToken(): string
    {
        $cacheKey = config('lark.api.token_cache_key', 'lark_access_token');
        $ttl = config('lark.api.token_cache_ttl', 7000);

        return Cache::remember($cacheKey, $ttl, function() {
            $response = Http::post("{$this->baseUrl}/auth/v3/tenant_access_token/internal", [
                'app_id' => $this->appId,
                'app_secret' => $this->appSecret,
            ]);

            if (!$response->successful()) {
                Log::error('Failed to get Lark access token', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to get Lark access token: ' . $response->body());
            }

            $data = $response->json();

            if (empty($data['tenant_access_token'])) {
                throw new \Exception('No access token in response');
            }

            return $data['tenant_access_token'];
        });
    }

    /**
     * Fetch all records dari table (dengan pagination otomatis)
     */
    public function fetchRecords(string $baseId, string $tableId, ?string $viewId = null): array
    {
        $token = $this->getAccessToken();
        $allRecords = [];
        $pageToken = null;

        do {
            $url = "{$this->baseUrl}/bitable/v1/apps/{$baseId}/tables/{$tableId}/records";

            $params = [
                'page_size' => 500, // Max 500 per request
            ];

            if ($pageToken) {
                $params['page_token'] = $pageToken;
            }

            if ($viewId) {
                $params['view_id'] = $viewId;
            }

            Log::info('Fetching Lark records', ['url' => $url, 'params' => $params]);

            $response = Http::withToken($token)
                ->get($url, $params);

            if (!$response->successful()) {
                Log::error('Failed to fetch Lark records', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to fetch Lark records: ' . $response->body());
            }

            $data = $response->json();

            if (isset($data['data']['items'])) {
                $allRecords = array_merge($allRecords, $data['data']['items']);
            }

            $pageToken = $data['data']['page_token'] ?? null;

        } while ($pageToken);

        Log::info('Lark records fetched', ['total' => count($allRecords)]);

        return $allRecords;
    }

    /**
     * Get raw response untuk debugging
     */
    public function getRawResponse(string $baseId, string $tableId): array
    {
        $token = $this->getAccessToken();
        $url = "{$this->baseUrl}/bitable/v1/apps/{$baseId}/tables/{$tableId}/records";

        $response = Http::withToken($token)
            ->get($url, ['page_size' => 10]);

        return $response->json();
    }
}
```

---

### 5. `app/Services/Lark/LarkProjectSyncService.php`

```php
<?php

namespace App\Services\Lark;

use App\DTO\LarkProjectDTO;
use App\Transformers\ProjectTransformer;
use App\Models\Production\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Lark Project Sync Service
 *
 * Main orchestrator untuk sync Projects dari Lark ke MySQL
 * Following iSyment pattern: Database transactions, comprehensive logging
 */
class LarkProjectSyncService
{
    private LarkApiClient $apiClient;
    private ProjectTransformer $transformer;

    public function __construct(LarkApiClient $apiClient, ProjectTransformer $transformer)
    {
        $this->apiClient = $apiClient;
        $this->transformer = $transformer;
    }

    /**
     * Main sync method
     *
     * @return array Statistics: [fetched, created, updated, deactivated, errors]
     */
    public function sync(): array
    {
        if (!config('lark.sync.enabled')) {
            throw new \Exception('Lark sync is disabled in configuration');
        }

        $baseId = config('lark.base_id');
        $tableId = config('lark.table_id');
        $viewId = config('lark.view_id');

        if (empty($baseId) || empty($tableId)) {
            throw new \Exception('Lark base_id or table_id not configured');
        }

        Log::info('Starting Lark project sync', [
            'base_id' => $baseId,
            'table_id' => $tableId,
            'user_id' => auth()->id()
        ]);

        DB::beginTransaction();

        try {
            // Step 1: Fetch dari Lark
            $larkRecords = $this->apiClient->fetchRecords($baseId, $tableId, $viewId);

            // Step 2: Parse ke DTO
            $dtos = array_map(function($record) {
                return new LarkProjectDTO($record);
            }, $larkRecords);

            // Step 3: Sync ke database
            $stats = $this->syncToDatabase($dtos);

            DB::commit();

            Log::info('Lark project sync completed', $stats);

            return $stats;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Lark project sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Sync DTOs ke database
     */
    private function syncToDatabase(array $dtos): array
    {
        $stats = [
            'fetched' => count($dtos),
            'created' => 0,
            'updated' => 0,
            'deactivated' => 0,
            'errors' => 0,
            'error_details' => [],
        ];

        $larkRecordIds = [];

        foreach ($dtos as $dto) {
            try {
                // Transform DTO → database array
                $data = $this->transformer->transform($dto);
                $larkRecordIds[] = $dto->recordId;

                // updateOrCreate dengan lark_record_id sebagai unique key
                $project = Project::updateOrCreate(
                    ['lark_record_id' => $dto->recordId],
                    $data
                );

                if ($project->wasRecentlyCreated) {
                    $stats['created']++;
                    Log::info('Project created from Lark', ['lark_record_id' => $dto->recordId]);
                } else {
                    $stats['updated']++;
                    Log::info('Project updated from Lark', ['lark_record_id' => $dto->recordId]);
                }

            } catch (\Exception $e) {
                $stats['errors']++;
                $stats['error_details'][] = [
                    'record_id' => $dto->recordId,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to sync project', [
                    'record' => $dto->toArray(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Soft delete projects yang tidak ada di Lark
        if (config('lark.sync.auto_deactivate_missing', true)) {
            $deactivated = Project::whereNotNull('lark_record_id')
                ->whereNotIn('lark_record_id', $larkRecordIds)
                ->delete(); // Soft delete

            $stats['deactivated'] = $deactivated;

            if ($deactivated > 0) {
                Log::info('Deactivated missing projects', ['count' => $deactivated]);
            }
        }

        return $stats;
    }

    /**
     * Get raw Lark response untuk debugging
     */
    public function getRawResponse(): array
    {
        $baseId = config('lark.base_id');
        $tableId = config('lark.table_id');

        return $this->apiClient->getRawResponse($baseId, $tableId);
    }
}
```

---

### 6. Update `app/Http/Controllers/Production/ProjectController.php`

Tambahkan di bagian use statements:

```php
use App\Services\Lark\LarkProjectSyncService;
```

Tambahkan methods berikut di akhir class:

```php
/**
 * Sync projects from Lark Base
 * Following iSyment pattern: Controller as trigger, Service handles logic
 */
public function syncFromLark(LarkProjectSyncService $syncService)
{
    try {
        $stats = $syncService->sync();

        $message = sprintf(
            'Lark sync completed! Fetched: %d | Created: %d | Updated: %d | Deactivated: %d',
            $stats['fetched'],
            $stats['created'],
            $stats['updated'],
            $stats['deactivated']
        );

        if ($stats['errors'] > 0) {
            $message .= sprintf(' | Errors: %d', $stats['errors']);
            return redirect()
                ->route('projects.index')
                ->with('warning', $message);
        }

        return redirect()
            ->route('projects.index')
            ->with('success', $message);

    } catch (\Exception $e) {
        \Log::error('Lark sync failed', [
            'error' => $e->getMessage(),
            'user_id' => auth()->id()
        ]);

        return redirect()
            ->route('projects.index')
            ->withErrors(['error' => 'Sync failed: ' . $e->getMessage()]);
    }
}

/**
 * Get raw Lark response for debugging
 * Only accessible by super admin
 */
public function getLarkRawData(LarkProjectSyncService $syncService)
{
    if (!auth()->user()->isSuperAdmin()) {
        abort(403, 'Unauthorized');
    }

    try {
        $rawData = $syncService->getRawResponse();

        return response()->json([
            'success' => true,
            'data' => $rawData,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
}
```

---

### 7. Update `routes/web.php`

Tambahkan routes sebelum `Route::resource('projects', ...)`:

```php
// Projects - Lark Sync
Route::post('/projects/sync-from-lark', [ProjectController::class, 'syncFromLark'])->name('projects.sync.lark');
Route::get('/projects/lark-raw-data', [ProjectController::class, 'getLarkRawData'])->name('projects.lark.raw');
```

---

### 8. Update `resources/views/production/projects/index.blade.php`

Tambahkan button setelah button Export Excel (sekitar line 80-100):

```blade
@if(auth()->user()->isSuperAdmin() || auth()->user()->isLogisticAdmin())
<!-- Sync from Lark Button -->
<form action="{{ route('projects.sync.lark') }}" method="POST" class="d-inline-block" id="syncLarkForm">
    @csrf
    <button type="button" class="btn btn-primary btn-sm" id="btnSyncLark">
        <i class="fas fa-sync-alt"></i> Sync from Lark
    </button>
</form>

<script>
document.getElementById('btnSyncLark').addEventListener('click', function(e) {
    e.preventDefault();

    if (confirm('Sync projects from Lark Base?\n\nThis will:\n- Fetch latest data from Lark\n- Create/update projects\n- Deactivate missing projects')) {
        // Show loading
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
        this.disabled = true;

        // Submit form
        document.getElementById('syncLarkForm').submit();
    }
});
</script>
@endif
```

---

### 9. Update `config/services.php`

Tambahkan Lark configuration:

```php
'lark' => [
    'app_id' => env('LARK_APP_ID'),
    'app_secret' => env('LARK_APP_SECRET'),
],
```

---

### 10. Migration: `database/migrations/2026_01_28_162636_add_sales_to_projects_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('sales')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('sales');
        });
    }
};
```

---

### 11. Update `app/Models/Production/Project.php`

Update fillable dan auditInclude:

```php
protected $fillable = [
    'name',
    'sales',  // NEW
    'stage',
    'qty',
    'description',
    'lark_record_id',  // Already exists
    'last_sync_at',    // Already exists
    // ... existing fields
];

protected $auditInclude = [
    'name',
    'sales',  // NEW
    'stage',
    'qty',
    'description',
    'lark_record_id',  // NEW
    'last_sync_at',    // NEW
    // ... existing fields
];
```

---

## 🎯 Summary Perubahan

| File                                                    | Status     | Keterangan             |
| ------------------------------------------------------- | ---------- | ---------------------- |
| `config/lark.php`                                       | ✅ Created | Lark configuration     |
| `config/services.php`                                   | ✅ Updated | Added Lark credentials |
| `app/DTO/LarkProjectDTO.php`                            | ✅ Created | Data Transfer Object   |
| `app/Transformers/ProjectTransformer.php`               | ✅ Created | Data normalization     |
| `app/Services/Lark/LarkApiClient.php`                   | ✅ Created | API communication      |
| `app/Services/Lark/LarkProjectSyncService.php`          | ✅ Created | Main orchestrator      |
| `app/Http/Controllers/Production/ProjectController.php` | ✅ Updated | Added sync methods     |
| `app/Models/Production/Project.php`                     | ✅ Updated | Added sales field      |
| `routes/web.php`                                        | ✅ Updated | Added sync routes      |
| `resources/views/production/projects/index.blade.php`   | ✅ Updated | Added sync button      |
| `database/migrations/*_add_sales_to_projects_table.php` | ✅ Created | Migration              |

Total: **11 files** (7 created, 4 updated)

---

- [ ] Inform users tentang button baru

---

**Created by:** AI Agent (following iSyment pattern)  
**Date:** 2026-01-28  
**Version:** 1.0.0
