<?php

namespace App\Transformers;

use App\DTO\LarkJobOrderDTO;
use App\Models\Production\Project;
use App\Models\Admin\Department;
use App\Services\Lark\LarkApiClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Job Order Transformer
 *
 * Bertugas melakukan normalisasi data dari DTO ke format database
 * TANGGUNG JAWAB:
 * - Trim string
 * - Normalisasi nilai
 * - Konversi tipe data
 * - Lookup foreign keys (project_id, department_id)
 * - Validasi business rules
 * - Download attachment via LarkApiClient (authenticated)
 */
class JobOrderTransformer
{
    /** In-memory dept cache — call preloadDepartments() once before bulk sync loop */
    private ?array $departmentCache = null;

    public function __construct(private readonly LarkApiClient $apiClient) {}

    /**
     * Pre-load all departments into memory (1 query total).
     * Eliminates N+1 DB queries for department lookups during bulk sync.
     * Call this ONCE before iterating records, same approach as Projects module.
     */
    public function preloadDepartments(): void
    {
        $this->departmentCache = Department::select(['id', 'name'])
            ->get()
            ->keyBy(fn($d) => strtolower(trim($d->name)))
            ->toArray();

        Log::debug('JobOrderTransformer: departments preloaded', ['count' => count($this->departmentCache)]);
    }

    /**
     * Transform Lark DTO to database-ready array
     *
     * @param LarkJobOrderDTO $dto
     * @return array Data siap disimpan ke database
     * @throws \InvalidArgumentException
     */
    /**
     * @param LarkJobOrderDTO $dto
     * @return array
     */
    public function transform(LarkJobOrderDTO $dto): array
    {
        return [
            'lark_record_id' => $dto->recordId,
            'name' => $this->normalizeName($dto->nameRaw),
            'project_lark' => $this->normalizeProjectLark($dto->projectRaw),
            'project_id' => $this->normalizeProjectId($dto->projectRaw),
            'department_lark' => $this->normalizeDepartmentLark($dto->departmentRaw),
            'department_id' => $this->normalizePrimaryDepartmentId($dto->departmentsArray),
            'delivery_date' => $this->parseDeliveryDate($dto->deliveryDateRaw),
            'status' => $this->normalizeStatus($dto->statusRaw),
            // Store Lark attachment URLs directly — same pattern as ProjectTransformer::normalizeImage().
            // URLs are refreshed every sync, so all records always have valid photo data.
            // No HTTP download needed here; the Lark tmp_url is usable immediately after sync.
            'final_image' => $this->extractFinalImageUrl($dto->finalImageRaw),
            'wip_photos' => $this->extractWipPhotoUrls($dto->wipPhotoRaw),
            'created_by' => 'Sync from Lark',
            'last_sync_at' => now(),
            '_department_ids' => $this->normalizeDepartmentIds($dto->departmentsArray),
        ];
    }

    /**
     * Extract first non-video attachment URL from Lark — mirrors ProjectTransformer::normalizeImage().
     * Stores the tmp_url directly; no HTTP download. URL refreshed on every sync.
     */
    private function extractFinalImageUrl(?array $attachments): ?string
    {
        if (empty($attachments) || !is_array($attachments)) {
            return null;
        }

        $first = $attachments[0] ?? null;
        if (!$first || !is_array($first)) {
            return null;
        }

        $url = $first['tmp_url'] ?? ($first['url'] ?? null);
        return $url ? substr(trim($url), 0, 500) : null;
    }

    /**
     * Extract all non-video attachment URLs from Lark WIP Images field.
     * Returns array of tmp_urls (JSON-stored). No HTTP download — same pattern as Projects module.
     * All records get photos on every sync; URLs refreshed automatically.
     */
    private function extractWipPhotoUrls(?array $attachments): ?array
    {
        if (empty($attachments) || !is_array($attachments)) {
            return null;
        }

        $videoExtensions = ['mp4', 'mov', 'avi', 'webm', 'mkv', 'flv', 'wmv', '3gp'];
        $urls = [];

        foreach ($attachments as $attachment) {
            if (!$attachment || !is_array($attachment)) {
                continue;
            }

            // Skip videos by mime type
            $mimeType = $attachment['mime_type'] ?? ($attachment['type'] ?? '');
            if ($mimeType && str_starts_with($mimeType, 'video/')) {
                continue;
            }

            // Skip videos by filename extension
            $name = $attachment['name'] ?? '';
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, $videoExtensions)) {
                continue;
            }

            $url = $attachment['tmp_url'] ?? ($attachment['url'] ?? null);
            if (!empty($url) && is_string($url)) {
                $urls[] = $url;
            }
        }

        return !empty($urls) ? $urls : null;
    }

    /**
     * Normalize job order name
     */
    private function normalizeName(?string $value): string
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('Job Order name cannot be empty');
        }

        // Trim dan remove multiple spaces
        $normalized = trim(preg_replace('/\s+/', ' ', $value));

        // Limit panjang sesuai database
        return substr($normalized, 0, 255);
    }

    /**
     * Normalize project text (raw dari Lark - untuk staging)
     *
     * CATATAN: Kolom project_lark hanya untuk menyimpan data mentah dari Lark
     * Data sebenarnya digunakan dari project_id (relasi)
     */
    private function normalizeProjectLark(?string $value): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        // Simpan as-is dari Lark
        return substr(trim($value), 0, 255);
    }

    /**
     * Normalize project to project_id
     *
     * Convert project name dari Lark ke project_id
     *
     * CATATAN: Project List di Lark bisa null
     */
    private function normalizeProjectId(?string $value): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        $projectName = trim($value);

        // Cari project berdasarkan nama (case-insensitive)
        $project = Project::whereRaw('LOWER(name) = ?', [strtolower($projectName)])->first();

        if (!$project) {
            Log::warning('Job Order sync: Project not found in database', [
                'lark_project_name' => $projectName,
            ]);
            return null;
        }

        return $project->id;
    }

    /**
     * Normalize department text (raw dari Lark - untuk staging)
     */
    private function normalizeDepartmentLark(?string $value): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        return substr(trim($value), 0, 255);
    }

    /**
     * Normalize department to department_id
     *
     * Convert department name dari Lark ke department_id
     */
    private function normalizeDepartmentId(?string $value): ?int
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }

        $departmentName = trim($value);
        $key = strtolower($departmentName);

        // Fast path: use preloaded in-memory cache (no DB query)
        if ($this->departmentCache !== null) {
            if (isset($this->departmentCache[$key])) {
                return (int) $this->departmentCache[$key]['id'];
            }
            // Fallback: strip "DCM " prefix
            $stripped = strtolower(preg_replace('/^DCM\s+/i', '', $departmentName));
            if ($stripped !== $key && isset($this->departmentCache[$stripped])) {
                return (int) $this->departmentCache[$stripped]['id'];
            }
            Log::warning('Job Order sync: Department not found', ['lark_department_name' => $departmentName]);
            return null;
        }

        // Slow path: direct DB query (cache not preloaded)
        $department = Department::whereRaw('LOWER(name) = ?', [$key])->first();
        if (!$department) {
            $stripped = preg_replace('/^DCM\s+/i', '', $departmentName);
            if (strtolower($stripped) !== $key) {
                $department = Department::whereRaw('LOWER(name) = ?', [strtolower($stripped)])->first();
            }
        }
        if (!$department) {
            Log::warning('Job Order sync: Department not found in database', ['lark_department_name' => $departmentName]);
            return null;
        }
        return $department->id;
    }

    /**
     * Normalize primary department (first in array) to department_id
     * For backward compatibility with existing department_id column
     *
     * @param array|null $departmentsArray Array of department names from Lark
     * @return int|null Department ID or null
     */
    private function normalizePrimaryDepartmentId(?array $departmentsArray): ?int
    {
        if (empty($departmentsArray)) {
            return null;
        }

        // Take first department as primary
        $primaryDeptName = trim($departmentsArray[0]);

        return $this->normalizeDepartmentId($primaryDeptName);
    }

    /**
     * Normalize all departments to array of department IDs
     * For many-to-many pivot table sync
     *
     * @param array|null $departmentsArray Array of department names from Lark
     * @return array Array of department IDs (may be empty)
     */
    private function normalizeDepartmentIds(?array $departmentsArray): array
    {
        if (empty($departmentsArray)) {
            return [];
        }

        $departmentIds = [];

        foreach ($departmentsArray as $deptName) {
            $deptName = trim($deptName);

            if (empty($deptName)) {
                continue;
            }

            $key = strtolower($deptName);
            $deptId = null;

            // Fast path: use preloaded in-memory cache
            if ($this->departmentCache !== null) {
                if (isset($this->departmentCache[$key])) {
                    $deptId = (int) $this->departmentCache[$key]['id'];
                } else {
                    $stripped = strtolower(preg_replace('/^DCM\s+/i', '', $deptName));
                    if ($stripped !== $key && isset($this->departmentCache[$stripped])) {
                        $deptId = (int) $this->departmentCache[$stripped]['id'];
                    }
                }
            } else {
                // Slow path: direct DB query
                $department = Department::whereRaw('LOWER(name) = ?', [$key])->first();
                if (!$department) {
                    $stripped = preg_replace('/^DCM\s+/i', '', $deptName);
                    if (strtolower($stripped) !== $key) {
                        $department = Department::whereRaw('LOWER(name) = ?', [strtolower($stripped)])->first();
                    }
                }
                $deptId = $department?->id;
            }

            if ($deptId) {
                $departmentIds[] = $deptId;
            } else {
                Log::warning('Job Order sync: Department not found for pivot', ['lark_department_name' => $deptName]);
            }
        }

        return array_unique($departmentIds);
    }

    /**
     * Parse delivery date from Lark format
     *
     * Examples:
     * - "2025-12-31" → Carbon date
     * - "2026-03-15" → Carbon date
     * - null/empty → null
     *
     * @param string|null $deliveryDateRaw Raw delivery date from Lark (YYYY-MM-DD)
     * @return string|null Formatted date string or null
     */
    private function parseDeliveryDate(?string $deliveryDateRaw): ?string
    {
        if (empty($deliveryDateRaw)) {
            return null;
        }

        try {
            // Check if it's a timestamp (integer as string)
            if (is_numeric($deliveryDateRaw)) {
                // Lark sends Unix timestamp in milliseconds
                $timestamp = (int) $deliveryDateRaw;

                // If timestamp > 10 digits, it's in milliseconds - convert to seconds
                if ($timestamp > 9999999999) {
                    $timestamp = (int) ($timestamp / 1000);
                }

                $date = \Carbon\Carbon::createFromTimestamp($timestamp);
                return $date->format('Y-m-d');
            }

            // Try parsing as date string (YYYY-MM-DD format)
            $date = \Carbon\Carbon::parse($deliveryDateRaw);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('Job Order sync: Could not parse delivery date', [
                'raw_value' => $deliveryDateRaw,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Normalize job status from Lark
     *
     * Handles various status formats from Lark and normalizes to consistent format
     *
     * Examples from Lark:
     * - "Preparing" → "Preparing"
     * - "Delivered" → "Delivered"
     * - "In Progress" → "In Progress"
     * - null/empty → null
     *
     * @param string|null $statusRaw Raw status from Lark
     * @return string|null Normalized status or null
     */
    private function normalizeStatus(?string $statusRaw): ?string
    {
        if (empty($statusRaw) || trim($statusRaw) === '') {
            return null;
        }

        // Trim and limit to 50 chars (database column limit)
        $status = trim($statusRaw);

        if (strlen($status) > 50) {
            Log::warning('Job Order sync: Status too long, truncating', [
                'raw_status' => $status,
                'truncated' => substr($status, 0, 50),
            ]);
            $status = substr($status, 0, 50);
        }

        return $status;
    }

    /**
     * Parse countdown days from Lark text format
     *
     * DEPRECATED: Not used anymore, replaced by delivery_date
     *
     * Examples:
     * - "2 days left" → 2
     * - "1 day left" → 1
     * - "5 Days Left" → 5 (case-insensitive)
     * - null/empty → null
     *
     * @param string|null $countdownRaw Raw countdown text from Lark
     * @return int|null Parsed integer or null
     */
    private function parseCountdownDays(?string $countdownRaw): ?int
    {
        if (empty($countdownRaw)) {
            return null;
        }

        // Extract number from text using regex
        // Match patterns: "2 days left", "1 day left", "3 Days", etc.
        if (preg_match('/(\d+)\s*day/i', $countdownRaw, $matches)) {
            return (int) $matches[1];
        }

        // If no pattern matched, log warning and return null
        Log::warning('Job Order sync: Could not parse countdown days', [
            'raw_value' => $countdownRaw,
        ]);

        return null;
    }

    /**
     * Normalize final image from Lark attachment — downloads to local storage
     *
     * Lark attachment URLs require authentication and expire.
     * Following InventoryTransformer pattern: download & save to storage/public.
     *
     * @param array|null $attachments Raw attachment array from Lark
     * @return string|null Local storage path (e.g. "job_order_images/lark_xxx.jpg") or null
     */
    private function normalizeFinalImage(?array $attachments): ?string
    {
        if (empty($attachments) || !is_array($attachments)) {
            return null;
        }

        // Get first attachment
        $firstAttachment = $attachments[0] ?? null;
        if (!$firstAttachment || !is_array($firstAttachment)) {
            return null;
        }

        // Prefer 'url' over 'tmp_url'
        $larkUrl = $firstAttachment['url'] ?? ($firstAttachment['tmp_url'] ?? null);

        if (empty($larkUrl) || !is_string($larkUrl)) {
            Log::warning('Job Order final_image: attachment has no valid URL', [
                'attachment' => $firstAttachment,
            ]);
            return null;
        }

        try {
            $response = $this->apiClient->downloadMedia($larkUrl);

            if (!$response || !$response->successful()) {
                Log::error('Job Order final_image: failed to download from Lark (check Authorization)', [
                    'url' => $larkUrl,
                    'status' => $response?->status(),
                ]);
                return null;
            }

            $extension = $this->getExtensionFromUrl($larkUrl) ?? 'jpg';
            $filename = 'lark_' . Str::random(40) . '.' . $extension;
            $path = 'job_order_images/' . $filename;

            Storage::disk('public')->put($path, $response->body());

            Log::info('Job Order final_image downloaded successfully', [
                'lark_url' => $larkUrl,
                'local_path' => $path,
            ]);

            return $path;
        } catch (\Exception $e) {
            Log::error('Job Order final_image: error during download', [
                'url' => $larkUrl,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Extract file extension from URL
     */
    private function getExtensionFromUrl(string $url): ?string
    {
        $urlPath = parse_url($url, PHP_URL_PATH);
        if ($urlPath) {
            $ext = pathinfo($urlPath, PATHINFO_EXTENSION);
            if ($ext && in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                return strtolower($ext);
            }
        }
        return 'jpg';
    }

    /**
     * Normalize WIP photo from Lark attachment — downloads first photo to local storage.
     * Skips videos by checking metadata ONLY (no download per attachment).
     * Only one download attempt is made (the first non-video found).
     *
     * @param array|null $attachments Raw attachment array from Lark
     * @return string|null Local storage path or null
     */
    private function normalizeWipPhoto(?array $attachments): ?string
    {
        if (empty($attachments) || !is_array($attachments)) {
            return null;
        }

        $videoExtensions = ['mp4', 'mov', 'avi', 'webm', 'mkv', 'flv', 'wmv', '3gp'];
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        // Find the first non-video attachment using metadata only (no HTTP request)
        $targetAttachment = null;
        foreach ($attachments as $attachment) {
            if (!$attachment || !is_array($attachment)) {
                continue;
            }

            // Check mime type
            $mimeType = $attachment['mime_type'] ?? ($attachment['type'] ?? '');
            if ($mimeType && str_starts_with($mimeType, 'video/')) {
                continue;
            }

            // Check extension from filename
            $name = $attachment['name'] ?? '';
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, $videoExtensions)) {
                continue;
            }

            $larkUrl = $attachment['url'] ?? ($attachment['tmp_url'] ?? null);
            if (empty($larkUrl) || !is_string($larkUrl)) {
                continue;
            }

            // Check extension from URL path
            $urlExt = strtolower(pathinfo(parse_url($larkUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
            if (in_array($urlExt, $videoExtensions)) {
                continue;
            }

            $targetAttachment = ['url' => $larkUrl, 'ext' => $ext ?: ($urlExt ?: 'jpg')];
            break; // Take the first non-video only
        }

        if (!$targetAttachment) {
            return null; // No photo found in attachments
        }

        // Single download attempt — no retry
        try {
            $response = $this->apiClient->downloadMedia($targetAttachment['url']);

            if (!$response || !$response->successful()) {
                Log::warning('Job Order wip_photo: download failed, skipping', [
                    'url' => $targetAttachment['url'],
                    'status' => $response?->status(),
                ]);
                return null;
            }

            $ext = in_array($targetAttachment['ext'], $imageExtensions) ? $targetAttachment['ext'] : $this->getExtensionFromUrl($targetAttachment['url']) ?? 'jpg';

            $filename = 'wip_' . Str::random(40) . '.' . $ext;
            $path = 'job_order_images/' . $filename;

            Storage::disk('public')->put($path, $response->body());

            Log::info('Job Order wip_photo downloaded successfully', [
                'local_path' => $path,
            ]);

            return $path;
        } catch (\Exception $e) {
            Log::error('Job Order wip_photo: error during download', [
                'url' => $targetAttachment['url'],
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Download ALL non-video attachments from Lark WIP Images field.
     * Returns JSON-serializable array of local storage paths.
     */
    /**
     * Public proxy for normalizeFinalImage — used by DownloadJobOrderPhotosJob.
     */
    public function normalizeFinalImagePublic(?array $attachments): ?string
    {
        return $this->normalizeFinalImage($attachments);
    }

    /**
     * Public proxy for normalizeWipPhotos — used by BackfillJobOrderWipPhotos command.
     */
    public function normalizeWipPhotosPublic(?array $attachments): ?array
    {
        return $this->normalizeWipPhotos($attachments);
    }

    private function normalizeWipPhotos(?array $attachments): ?array
    {
        if (empty($attachments) || !is_array($attachments)) {
            return null;
        }

        $videoExtensions = ['mp4', 'mov', 'avi', 'webm', 'mkv', 'flv', 'wmv', '3gp'];
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $paths = [];

        foreach ($attachments as $attachment) {
            if (!$attachment || !is_array($attachment)) {
                continue;
            }

            // Skip videos by mime type
            $mimeType = $attachment['mime_type'] ?? ($attachment['type'] ?? '');
            if ($mimeType && str_starts_with($mimeType, 'video/')) {
                continue;
            }

            // Skip videos by filename extension
            $name = $attachment['name'] ?? '';
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, $videoExtensions)) {
                continue;
            }

            $larkUrl = $attachment['url'] ?? ($attachment['tmp_url'] ?? null);
            if (empty($larkUrl) || !is_string($larkUrl)) {
                continue;
            }

            // Skip videos by URL extension
            $urlExt = strtolower(pathinfo(parse_url($larkUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
            if (in_array($urlExt, $videoExtensions)) {
                continue;
            }

            $finalExt = $ext ?: ($urlExt ?: 'jpg');
            if (!in_array($finalExt, $imageExtensions)) {
                $finalExt = 'jpg';
            }

            try {
                $response = $this->apiClient->downloadMedia($larkUrl);
                if (!$response || !$response->successful()) {
                    Log::warning('Job Order wip_photos: download failed, skipping', [
                        'url' => $larkUrl,
                        'status' => $response?->status(),
                    ]);
                    continue;
                }

                $filename = 'wip_' . Str::random(40) . '.' . $finalExt;
                $path = 'job_order_images/' . $filename;
                Storage::disk('public')->put($path, $response->body());
                $paths[] = $path;

                Log::info('Job Order wip_photos: photo downloaded', ['path' => $path]);
            } catch (\Exception $e) {
                Log::error('Job Order wip_photos: error during download', [
                    'url' => $larkUrl,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return !empty($paths) ? $paths : null;
    }

    /**
     * Validate transformed data
     *
     * @param array $data
     * @throws \InvalidArgumentException
     */
    public function validate(array $data): void
    {
        // Skip if name is empty or only whitespace
        if (empty($data['name']) || trim($data['name']) === '') {
            throw new \InvalidArgumentException('Job Order name is required');
        }

        if (empty($data['lark_record_id'])) {
            throw new \InvalidArgumentException('Lark record_id is required');
        }

        // Project dan Department tidak wajib (bisa null)
        // Hanya log warning jika tidak ditemukan (sudah di-handle di normalize methods)
    }
}
