<?php

namespace App\Services;

use App\Models\Hr\Employee;
use App\Models\Hr\LeaveRequest;
use App\Models\Hr\OvertimeRequest;
use App\Models\Logistic\Inventory;
use App\Models\Logistic\MaterialRequest;
use App\Models\Procurement\PurchaseRequest;
use App\Models\Procurement\Shipping;
use App\Models\Production\JobOrder;
use App\Models\Production\Project;
use Illuminate\Support\Facades\Auth;

/**
 * ChatbotToolService
 *
 * Semua implementasi tool untuk Symcore AI Chatbot.
 * Role-based access control diterapkan di setiap method.
 *
 * ATURAN KEAMANAN YANG TIDAK BOLEH DILANGGAR:
 *  - salary / gaji          → TIDAK PERNAH dikembalikan ke siapapun
 *  - price_per_unit / harga → hanya admin_finance / super_admin / admin
 *  - freight_price / biaya  → hanya admin_finance / super_admin / admin
 *  - sales / budget proyek  → hanya admin_finance / super_admin / admin
 */
class ChatbotToolService
{
    // Lazy — resolved saat pertama kali dibutuhkan, bukan di constructor
    private function user()
    {
        return Auth::user();
    }

    // =========================================================
    // ROLE HELPERS
    // =========================================================

    /** super_admin | admin | admin_hr | hr */
    private function isHrPrivileged(): bool
    {
        $user = $this->user();
        return $user && in_array($user->role, ['super_admin', 'admin', 'admin_hr', 'hr']);
    }

    /** super_admin | admin | admin_finance */
    private function isFinanceAdmin(): bool
    {
        $user = $this->user();
        return $user && in_array($user->role, ['super_admin', 'admin', 'admin_finance']);
    }

    /** super_admin | admin | admin_logistic | admin_finance */
    private function isLogisticAdmin(): bool
    {
        $user = $this->user();
        return $user && in_array($user->role, ['super_admin', 'admin', 'admin_logistic', 'admin_finance']);
    }

    /**
     * Temukan Employee yang terhubung dengan user yang sedang login.
     * Strategi: username match (primer) → email match (fallback).
     */
    private function findMyEmployee(): ?Employee
    {
        $user = $this->user();
        if (!$user) {
            return null;
        }

        $employee = null;

        // 1. Match by username (paling reliabel)
        if (!blank($user->username)) {
            $employee = Employee::where('username', $user->username)->first();
        }

        // 2. Fallback: match by email jika User model memiliki kolom email
        if (!$employee && property_exists($user, 'email') && !blank($user->email)) {
            $employee = Employee::where('email', $user->email)->first();
        }

        return $employee;
    }

    /**
     * Periksa apakah Employee tertentu adalah employee yang login.
     */
    private function isSelfEmployee(Employee $emp): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        // Match by username
        if (!blank($user->username) && !blank($emp->username)) {
            return strtolower($emp->username) === strtolower($user->username);
        }
        // Fallback: match by email
        if (property_exists($user, 'email') && !blank($user->email) && !blank($emp->email)) {
            return strtolower($emp->email) === strtolower($user->email);
        }
        return false;
    }

    // =========================================================
    // TOOL: cari_karyawan
    // HR/admin → detail lengkap | Self → detail lengkap | Lainnya → info publik
    // =========================================================
    public function cariKaryawan(string $nama): array
    {
        if (blank($nama)) {
            return ['error' => 'Parameter nama tidak boleh kosong.'];
        }

        $isPrivileged = $this->isHrPrivileged();

        $employees = Employee::with('department')
            ->where('name', 'LIKE', "%{$nama}%")
            ->orderBy('name')
            ->limit(5)
            ->get();

        if ($employees->isEmpty()) {
            return ['found' => false, 'message' => "Tidak ditemukan karyawan dengan nama yang mengandung '{$nama}'."];
        }

        $results = $employees->map(function (Employee $emp) use ($isPrivileged) {
            $isSelf = $this->isSelfEmployee($emp);

            if ($isPrivileged || $isSelf) {
                return [
                    'employee_no' => $emp->employee_no,
                    'name' => $emp->name,
                    'email' => $emp->email ?? '-',
                    'position' => $emp->position ?? '-',
                    'department' => $emp->department?->name ?? '-',
                    'employment_type' => $emp->employment_type ?? '-',
                    'status' => $emp->status ?? '-',
                    'hire_date' => $emp->hire_date?->format('d M Y') ?? '-',
                    'phone' => $emp->phone ?? '-',
                    'saldo_cuti' => ($emp->saldo_cuti ?? 0) . ' hari',
                    // salary intentionally excluded
                ];
            }

            // Info publik saja
            return [
                'name' => $emp->name,
                'position' => $emp->position ?? '-',
                'department' => $emp->department?->name ?? '-',
                'status' => $emp->status ?? '-',
            ];
        });

        return [
            'found' => true,
            'count' => $employees->count(),
            'results' => $results->values()->toArray(),
        ];
    }

    // =========================================================
    // TOOL: cek_stok_material
    // Semua user bisa lihat stok; harga hanya untuk finance/admin
    // =========================================================
    public function cekStokMaterial(string $nama): array
    {
        if (blank($nama)) {
            return ['error' => 'Parameter nama_material tidak boleh kosong.'];
        }

        $items = Inventory::with(['category', 'unitRelation', 'location', 'supplier'])
            ->where('name', 'LIKE', "%{$nama}%")
            ->orderBy('name')
            ->limit(8)
            ->get();

        if ($items->isEmpty()) {
            return ['found' => false, 'message' => "Tidak ditemukan material dengan nama yang mengandung '{$nama}'."];
        }

        $isFinance = $this->isFinanceAdmin();

        $results = $items->map(function (Inventory $item) use ($isFinance) {
            $data = [
                'name' => $item->name,
                'quantity' => (float) ($item->quantity ?? 0),
                'unit' => $item->unit_name ?: '-',
                'category' => $item->category?->name ?? '-',
                'location' => $item->location?->name ?? '-',
                'supplier' => $item->supplier?->name ?? '-',
                'remark' => $item->remark ?? '-',
            ];
            // Harga hanya untuk finance/admin
            if ($isFinance) {
                $data['cost_price'] = $item->cost_price ?? null;
                $data['cost_allocation_method'] = $item->cost_allocation_method ?? null;
            }
            return $data;
        });

        return [
            'found' => true,
            'count' => $items->count(),
            'results' => $results->values()->toArray(),
        ];
    }

    // =========================================================
    // TOOL: get_purchase_requests
    // Semua user bisa lihat; harga hanya untuk finance/admin
    // =========================================================
    public function getPurchaseRequests(string $keyword): array
    {
        if (blank($keyword)) {
            return ['error' => 'Parameter keyword tidak boleh kosong.'];
        }

        $prs = PurchaseRequest::with(['inventory', 'supplier', 'project', 'user'])
            ->where(function ($q) use ($keyword) {
                $q->where('material_name', 'LIKE', "%{$keyword}%")->orWhereHas('inventory', fn($q2) => $q2->where('name', 'LIKE', "%{$keyword}%"));
            })
            ->latest()
            ->limit(8)
            ->get();

        if ($prs->isEmpty()) {
            return ['found' => false, 'message' => "Tidak ditemukan purchase request untuk keyword '{$keyword}'."];
        }

        $isFinance = $this->isFinanceAdmin();

        $results = $prs->map(function (PurchaseRequest $pr) use ($isFinance) {
            $data = [
                'id' => $pr->id,
                'material_name' => $pr->material_name ?? ($pr->inventory?->name ?? '-'),
                'type' => $pr->type ?? '-',
                'required_qty' => (float) ($pr->required_quantity ?? 0),
                'qty_to_buy' => (float) ($pr->qty_to_buy ?? 0),
                'unit' => $pr->unit ?? '-',
                'approval_status' => $pr->approval_status ?? '-',
                'supplier' => $pr->supplier?->name ?? '-',
                'project' => $pr->project?->name ?? '-',
                'requested_by' => $pr->user?->username ?? '-',
                'delivery_date' => $pr->delivery_date?->format('d M Y') ?? '-',
                'shipping_status' => $pr->getShippingStatus(),
                'remark' => $pr->remark ?? '-',
            ];
            // Harga hanya untuk finance/admin
            if ($isFinance && $pr->price_per_unit) {
                $data['price_per_unit'] = (float) $pr->price_per_unit;
                $data['currency'] = $pr->currency?->code ?? '-';
            }
            return $data;
        });

        return [
            'found' => true,
            'count' => $prs->count(),
            'results' => $results->values()->toArray(),
        ];
    }

    // =========================================================
    // TOOL: cek_saldo_cuti
    // HR/admin → semua karyawan | Lainnya → hanya diri sendiri
    // =========================================================
    public function cekSaldoCuti(string $nama): array
    {
        if (blank($nama)) {
            return ['error' => 'Parameter nama_karyawan tidak boleh kosong.'];
        }

        $isPrivileged = $this->isHrPrivileged();

        $employee = Employee::with('department')
            ->where('name', 'LIKE', "%{$nama}%")
            ->first();

        if (!$employee) {
            return ['found' => false, 'message' => "Karyawan dengan nama '{$nama}' tidak ditemukan."];
        }

        $isSelf = $this->isSelfEmployee($employee);

        if (!$isPrivileged && !$isSelf) {
            return [
                'error' => 'access_denied',
                'message' => 'Anda tidak memiliki akses untuk melihat saldo cuti karyawan lain. Anda hanya bisa mengecek saldo cuti milik Anda sendiri.',
            ];
        }

        $leaves = LeaveRequest::where('employee_id', $employee->id)->whereYear('start_date', now()->year)->orderByDesc('start_date')->limit(5)->get()->map(
            fn(LeaveRequest $l) => [
                'type' => $l->type_label ?? $l->type,
                'start' => $l->start_date?->format('d M Y'),
                'end' => $l->end_date?->format('d M Y'),
                'duration' => ($l->duration ?? 0) . ' hari',
                'status' => $l->approval_1 === 'approved' && $l->approval_2 === 'approved' ? 'Disetujui' : 'Pending/Sebagian',
            ],
        );

        return [
            'found' => true,
            'name' => $employee->name,
            'employee_no' => $employee->employee_no,
            'department' => $employee->department?->name ?? '-',
            'saldo_cuti' => (float) ($employee->saldo_cuti ?? 0),
            'saldo_cuti_text' => ($employee->saldo_cuti ?? 0) . ' hari',
            'riwayat_cuti' => $leaves->values()->toArray(),
        ];
    }

    // =========================================================
    // TOOL: cari_proyek
    // Semua user bisa lihat info proyek; sales/budget hanya finance/admin
    // =========================================================
    public function cariProyek(string $keyword): array
    {
        if (blank($keyword)) {
            return ['error' => 'Parameter keyword tidak boleh kosong.'];
        }

        $projects = Project::with(['department', 'status'])
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', "%{$keyword}%")
                    ->orWhere('stage', 'LIKE', "%{$keyword}%")
                    ->orWhere('type_dept', 'LIKE', "%{$keyword}%");
            })
            ->orderBy('name')
            ->limit(10)
            ->get();

        if ($projects->isEmpty()) {
            return ['found' => false, 'message' => "Tidak ditemukan proyek dengan keyword '{$keyword}'."];
        }

        $isFinance = $this->isFinanceAdmin();

        $results = $projects->map(function (Project $p) use ($isFinance) {
            $deadline = $p->deadline;
            if ($deadline && !is_string($deadline)) {
                $deadline = $deadline->format('d M Y');
            }

            $data = [
                'name' => $p->name,
                'type_dept' => $p->type_dept ?? '-',
                'department' => $p->department?->name ?? '-',
                'status' => $p->status?->name ?? ($p->project_status ?? '-'),
                'stage' => $p->stage ?? '-',
                'qty' => $p->qty ?? '-',
                'start_date' => $p->start_date?->format('d M Y') ?? '-',
                'deadline' => $deadline ?? '-',
                'finish_date' => $p->finish_date?->format('d M Y') ?? '-',
                'source' => $p->isFromLark() ? 'Lark (Valid)' : 'Legacy',
            ];

            // Sales/budget hanya untuk finance/admin
            if ($isFinance && $p->sales) {
                $data['sales'] = $p->sales;
            }

            return $data;
        });

        return [
            'found' => true,
            'count' => $projects->count(),
            'results' => $results->values()->toArray(),
        ];
    }

    // =========================================================
    // TOOL: get_job_orders
    // Semua user bisa lihat job order (tidak ada data finansial)
    // =========================================================
    public function getJobOrders(string $keyword): array
    {
        if (blank($keyword)) {
            return ['error' => 'Parameter keyword tidak boleh kosong.'];
        }

        $jos = JobOrder::with(['project', 'department'])
            ->where(function ($q) use ($keyword) {
                $q->where('id', 'LIKE', "%{$keyword}%")
                    ->orWhere('name', 'LIKE', "%{$keyword}%")
                    ->orWhere('description', 'LIKE', "%{$keyword}%")
                    ->orWhereHas('project', fn($q2) => $q2->where('name', 'LIKE', "%{$keyword}%"));
            })
            ->latest()
            ->limit(8)
            ->get();

        if ($jos->isEmpty()) {
            return ['found' => false, 'message' => "Tidak ditemukan job order dengan keyword '{$keyword}'."];
        }

        $results = $jos->map(
            fn(JobOrder $jo) => [
                'id' => $jo->id,
                'name' => $jo->name,
                'project' => $jo->project?->name ?? ($jo->project_lark ?? '-'),
                'department' => $jo->department?->name ?? ($jo->department_lark ?? '-'),
                'start_date' => $jo->start_date?->format('d M Y') ?? '-',
                'end_date' => $jo->end_date?->format('d M Y') ?? '-',
                'actual_start' => $jo->actual_start_date?->format('d M Y') ?? '-',
                'actual_end' => $jo->actual_end_date?->format('d M Y') ?? '-',
                'description' => $jo->description ?? '-',
            ],
        );

        return [
            'found' => true,
            'count' => $jos->count(),
            'results' => $results->values()->toArray(),
        ];
    }

    // =========================================================
    // TOOL: get_job_order_materials
    // Cari SEMUA material request untuk satu job order tertentu.
    // Input: ID job order (JO-XXXXXX) ATAU sebagian nama job order.
    // Semua user bisa lihat; harga inventori tidak pernah dikembalikan.
    // =========================================================
    public function getJobOrderMaterials(string $keyword): array
    {
        if (blank($keyword)) {
            return [
                'error' => 'parameter_kosong',
                'message' => 'Masukkan ID job order (contoh: JO-260207002) atau nama job order.',
            ];
        }

        // ── Cari job order: exact ID dulu, lalu LIKE pada name ──
        $jobOrder = JobOrder::with(['project', 'department'])
            ->where('id', $keyword)
            ->first();

        if (!$jobOrder) {
            $jobOrder = JobOrder::with(['project', 'department'])
                ->where('name', 'LIKE', "%{$keyword}%")
                ->orderBy('name')
                ->first();
        }

        if (!$jobOrder) {
            return [
                'found' => false,
                'message' => "Job order '{$keyword}' tidak ditemukan. " . 'Coba gunakan ID (JO-XXXXXX) atau nama yang lebih spesifik.',
            ];
        }

        // ── Ambil semua material request untuk job order ini ──
        $requests = MaterialRequest::with(['inventory.unitRelation', 'inventory.category', 'inventory.location'])
            ->where('job_order_id', $jobOrder->id)
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'delivered', 'canceled')")
            ->orderBy('created_at')
            ->get();

        if ($requests->isEmpty()) {
            return [
                'found' => true,
                'job_order' => [
                    'id' => $jobOrder->id,
                    'name' => $jobOrder->name,
                    'project' => $jobOrder->project?->name ?? ($jobOrder->project_lark ?? '-'),
                    'department' => $jobOrder->department?->name ?? ($jobOrder->department_lark ?? '-'),
                ],
                'message' => "Job order '{$jobOrder->name}' (ID: {$jobOrder->id}) belum memiliki material request.",
            ];
        }

        // ── Bangun daftar material ──
        $materials = $requests->map(function (MaterialRequest $mr) {
            $inv = $mr->inventory;

            // Unit: gunakan accessor unit_name (FK unitRelation → fallback varchar)
            $unitName = $inv?->unit_name ?: '-';

            return [
                'material_name' => $inv?->name ?? '-',
                'category' => $inv?->category?->name ?? '-',
                'location' => $inv?->location?->name ?? '-',
                'current_stock' => (float) ($inv?->quantity ?? 0),
                'qty_requested' => (float) ($mr->qty ?? 0),
                'qty_processed' => (float) ($mr->processed_qty ?? 0),
                'qty_remaining' => (float) ($mr->remaining_qty ?? 0),
                'unit' => $unitName,
                'status' => $mr->status,
                'requested_by' => $mr->requested_by ?? '-',
                'approved_at' => $mr->approved_at?->format('d M Y H:i') ?? '-',
                'remark' => $mr->remark ?? '-',
                'requested_date' => $mr->created_at?->format('d M Y') ?? '-',
                // price / cost intentionally excluded
            ];
        });

        // ── Ringkasan per status ──
        $statusSummary = $requests->groupBy('status')->map(fn($group) => $group->count())->toArray();

        return [
            'found' => true,
            'job_order' => [
                'id' => $jobOrder->id,
                'name' => $jobOrder->name,
                'project' => $jobOrder->project?->name ?? ($jobOrder->project_lark ?? '-'),
                'department' => $jobOrder->department?->name ?? ($jobOrder->department_lark ?? '-'),
                'start_date' => $jobOrder->start_date?->format('d M Y') ?? '-',
                'end_date' => $jobOrder->end_date?->format('d M Y') ?? '-',
            ],
            'total_materials' => $requests->count(),
            'status_summary' => $statusSummary,
            'materials' => $materials->values()->toArray(),
        ];
    }

    // =========================================================
    // TOOL: get_material_requests
    // User biasa → hanya milik sendiri | Logistic/admin → semua
    // CATATAN: ini untuk melihat DAFTAR REQUEST MILIK USER, bukan per job order.
    //          Untuk material per job order, gunakan get_job_order_materials.
    // =========================================================
    public function getMaterialRequests(string $status = '', string $keyword = ''): array
    {
        $isAdmin = $this->isLogisticAdmin();
        $query = MaterialRequest::with(['inventory', 'project', 'jobOrder']);

        // Non-admin: hanya permintaan milik sendiri
        if (!$isAdmin) {
            $query->where('requested_by', $this->user()?->username);
        }

        // Filter by status
        if (!blank($status)) {
            $validStatuses = ['pending', 'approved', 'delivered', 'canceled'];
            if (in_array(strtolower($status), $validStatuses)) {
                $query->where('status', strtolower($status));
            }
        }

        // Filter by keyword (material atau proyek)
        if (!blank($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->whereHas('inventory', fn($q2) => $q2->where('name', 'LIKE', "%{$keyword}%"))->orWhereHas('project', fn($q2) => $q2->where('name', 'LIKE', "%{$keyword}%"));
            });
        }

        $mrs = $query->latest()->limit(10)->get();

        if ($mrs->isEmpty()) {
            $msg = $isAdmin ? 'Tidak ada material request yang ditemukan.' : 'Tidak ada material request atas nama Anda yang ditemukan.';
            return ['found' => false, 'message' => $msg];
        }

        $results = $mrs->map(
            fn(MaterialRequest $mr) => [
                'id' => $mr->id,
                'material' => $mr->inventory?->name ?? '-',
                'qty' => (float) ($mr->qty ?? 0),
                'processed_qty' => (float) ($mr->processed_qty ?? 0),
                'remaining_qty' => (float) ($mr->remaining_qty ?? 0),
                'project' => $mr->project_name,
                'job_order' => $mr->jobOrder?->name ?? '-',
                'status' => $mr->status,
                'requested_by' => $mr->requested_by ?? '-',
                'approved_at' => $mr->approved_at?->format('d M Y H:i') ?? '-',
                'remark' => $mr->remark ?? '-',
                'created_at' => $mr->created_at?->format('d M Y') ?? '-',
            ],
        );

        return [
            'found' => true,
            'count' => $mrs->count(),
            'note' => $isAdmin ? 'Data semua departemen' : 'Data permintaan Anda sendiri',
            'results' => $results->values()->toArray(),
        ];
    }

    // =========================================================
    // TOOL: get_leave_requests
    // User biasa → hanya milik sendiri | HR/admin → semua atau filter nama
    // =========================================================
    public function getLeaveRequests(string $status = '', string $employee_name = ''): array
    {
        $isPrivileged = $this->isHrPrivileged();
        $query = LeaveRequest::with(['employee.department']);

        if ($isPrivileged) {
            // HR/admin: bisa filter berdasarkan nama karyawan
            if (!blank($employee_name)) {
                $query->whereHas('employee', fn($q) => $q->where('name', 'LIKE', "%{$employee_name}%"));
            }
        } else {
            // User biasa: hanya milik sendiri
            $employee = $this->findMyEmployee();
            if (!$employee) {
                return [
                    'error' => 'employee_not_found',
                    'message' => 'Data karyawan Anda tidak ditemukan di sistem. Silakan hubungi HR.',
                ];
            }
            $query->where('employee_id', $employee->id);
        }

        // Filter by status
        if (!blank($status)) {
            $s = strtolower($status);
            if ($s === 'approved') {
                $query->where('approval_1', 'approved')->where('approval_2', 'approved');
            } elseif ($s === 'pending') {
                $query->where(function ($q) {
                    $q->where('approval_1', 'pending')->orWhere('approval_2', 'pending');
                });
            } elseif ($s === 'rejected') {
                $query->where(function ($q) {
                    $q->where('approval_1', 'rejected')->orWhere('approval_2', 'rejected');
                });
            }
        }

        $leaves = $query->orderByDesc('start_date')->limit(10)->get();

        if ($leaves->isEmpty()) {
            return ['found' => false, 'message' => 'Tidak ada data cuti yang ditemukan.'];
        }

        $results = $leaves->map(
            fn(LeaveRequest $l) => [
                'employee' => $l->employee?->name ?? '-',
                'department' => $l->employee?->department?->name ?? '-',
                'type' => $l->type_label ?? $l->type,
                'start' => $l->start_date?->format('d M Y'),
                'end' => $l->end_date?->format('d M Y'),
                'duration' => ($l->duration ?? 0) . ' hari',
                'reason' => $l->reason ?? '-',
                'approval_1' => $l->approval_1 ?? 'pending',
                'approval_2' => $l->approval_2 ?? 'pending',
                'status' => $l->approval_1 === 'approved' && $l->approval_2 === 'approved' ? 'Disetujui' : ($l->approval_1 === 'rejected' || $l->approval_2 === 'rejected' ? 'Ditolak' : 'Pending'),
            ],
        );

        return [
            'found' => true,
            'count' => $leaves->count(),
            'note' => $isPrivileged ? 'Data semua karyawan' : 'Data cuti Anda',
            'results' => $results->values()->toArray(),
        ];
    }

    // =========================================================
    // TOOL: get_overtime_requests
    // User biasa → hanya milik sendiri | HR/admin → semua atau filter nama
    // Pay detail (total_pay) hanya dikembalikan untuk HR/admin
    // =========================================================
    public function getOvertimeRequests(string $status = '', string $employee_name = ''): array
    {
        $isPrivileged = $this->isHrPrivileged();
        $query = OvertimeRequest::with(['employee.department', 'jobOrder', 'payDetail']);

        if ($isPrivileged) {
            if (!blank($employee_name)) {
                $query->whereHas('employee', fn($q) => $q->where('name', 'LIKE', "%{$employee_name}%"));
            }
        } else {
            $employee = $this->findMyEmployee();
            if (!$employee) {
                return [
                    'error' => 'employee_not_found',
                    'message' => 'Data karyawan Anda tidak ditemukan di sistem. Silakan hubungi HR.',
                ];
            }
            $query->where('employee_id', $employee->id);
        }

        if (!blank($status)) {
            $query->where('status', strtolower($status));
        }

        $ots = $query->orderByDesc('start_time')->limit(10)->get();

        if ($ots->isEmpty()) {
            return ['found' => false, 'message' => 'Tidak ada data lembur yang ditemukan.'];
        }

        $results = $ots->map(function (OvertimeRequest $ot) use ($isPrivileged) {
            $data = [
                'employee' => $ot->employee?->name ?? '-',
                'department' => $ot->employee?->department?->name ?? ($ot->department?->name ?? '-'),
                'job_order' => $ot->jobOrder?->name ?? '-',
                'ot_code' => $ot->ot_code ?? '-',
                'date' => $ot->start_time?->format('d M Y') ?? '-',
                'start_time' => $ot->start_time?->format('H:i') ?? '-',
                'end_time' => $ot->end_time?->format('H:i') ?? '-',
                'net_hours' => $ot->net_hours_formatted ?? ((float) ($ot->net_hours ?? 0)) . ' jam',
                'hr_status' => $ot->hr_approval_status ?? 'pending',
                'dir_status' => $ot->director_approval_status ?? 'pending',
                'final_status' => $ot->status ?? '-',
                'reason' => $ot->reason ?? '-',
            ];
            // Total pay hanya untuk HR/admin (tidak boleh bocor ke user biasa)
            if ($isPrivileged && $ot->payDetail) {
                $data['total_pay_calculated'] = true;
                // Nominal tidak ditampilkan, hanya konfirmasi sudah dihitung
            }
            return $data;
        });

        return [
            'found' => true,
            'count' => $ots->count(),
            'note' => $isPrivileged ? 'Data semua karyawan' : 'Data lembur Anda',
            'results' => $results->values()->toArray(),
        ];
    }

    // =========================================================
    // TOOL: cek_status_pengiriman
    // Semua user bisa lihat status; freight_price hanya untuk finance/admin
    // =========================================================
    public function cekStatusPengiriman(string $keyword): array
    {
        if (blank($keyword)) {
            return ['error' => 'Parameter keyword tidak boleh kosong.'];
        }

        $isFinance = $this->isFinanceAdmin();

        $shipments = Shipping::with(['details', 'goodsReceive'])
            ->where(function ($q) use ($keyword) {
                $q->where('international_waybill_no', 'LIKE', "%{$keyword}%")->orWhere('freight_company', 'LIKE', "%{$keyword}%");
            })
            ->latest()
            ->limit(5)
            ->get();

        if ($shipments->isEmpty()) {
            return ['found' => false, 'message' => "Tidak ditemukan pengiriman dengan keyword '{$keyword}'."];
        }

        $results = $shipments->map(function (Shipping $s) use ($isFinance) {
            $eta = $s->eta_to_arrived;
            if ($eta && !is_string($eta)) {
                try {
                    $eta = $eta->format('d M Y');
                } catch (\Exception $e) {
                }
            }

            $data = [
                'waybill_no' => $s->international_waybill_no ?? '-',
                'freight_company' => $s->freight_company ?? '-',
                'method' => $s->freight_method ?? '-',
                'status' => $s->shipment_status ?? '-',
                'eta' => $eta ?? '-',
                'items_count' => $s->details->count(),
                'received' => $s->goodsReceive ? 'Sudah diterima' : 'Belum diterima',
                'remarks' => $s->remarks ?? '-',
            ];

            // Biaya pengiriman hanya untuk finance/admin
            if ($isFinance) {
                $data['freight_price'] = $s->freight_price ?? null;
            }

            return $data;
        });

        return [
            'found' => true,
            'count' => $shipments->count(),
            'results' => $results->values()->toArray(),
        ];
    }
}
