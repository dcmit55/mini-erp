<?php

namespace App\Services;

use App\Models\Hr\Employee;
use App\Models\Hr\LeaveRequest;
use App\Models\Hr\OvertimeRequest;
use App\Models\Logistic\Inventory;
use App\Models\Procurement\PurchaseRequest;
use App\Models\Production\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ChatbotContextService
{
    public function buildSystemPrompt(): string
    {
        $user    = Auth::user();
        $context = $this->gatherData();

        return $this->format($user, $context);
    }

    /**
     * Kumpulkan statistik agregat dari database.
     * Di-cache 60 detik untuk menghindari query berulang di setiap pesan.
     */
    private function gatherData(): array
    {
        return Cache::remember('chatbot_context_stats', 60, function () {
            $data = [];

            try {
                $data['hr_pending_ot']       = OvertimeRequest::where('status', 'submitted')
                    ->where('hr_approval_status', 'pending')->count();
                $data['director_pending_ot'] = OvertimeRequest::where('status', 'submitted')
                    ->where('director_approval_status', 'pending')->count();
                $data['ot_approved_month']   = OvertimeRequest::where('status', 'approved')
                    ->whereMonth('updated_at', now()->month)
                    ->whereYear('updated_at', now()->year)->count();
            } catch (\Exception) {}

            try {
                $data['pending_leaves']  = LeaveRequest::where(function ($q) {
                    $q->where('approval_1', 'pending')->orWhere('approval_2', 'pending');
                })->count();
                $data['approved_leaves'] = LeaveRequest::where('approval_1', 'approved')
                    ->where('approval_2', 'approved')
                    ->whereMonth('updated_at', now()->month)
                    ->whereYear('updated_at', now()->year)->count();
            } catch (\Exception) {}

            try {
                $data['active_employees'] = Employee::whereNull('deleted_at')->count();
            } catch (\Exception) {}

            try {
                $data['lark_projects'] = Project::fromLark()->count();
                $data['all_projects']  = Project::count();
            } catch (\Exception) {}

            try {
                $data['total_inventory'] = Inventory::count();
                // low_stock hanya dihitung jika kolom minimum_stock ada
                $data['low_stock'] = Inventory::whereRaw('quantity <= minimum_stock')->count();
            } catch (\Exception) {}

            try {
                $data['pending_purchases'] = PurchaseRequest::where('approval_status', 'Pending')->count();
            } catch (\Exception) {}

            return $data;
        });
    }

    private function format($user, array $d): string
    {
        $name  = $user?->name ?? $user?->username ?? 'User';
        $role  = $user?->role ?? 'user';
        $today = now()->format('d M Y, H:i');

        $stats = implode(' | ', array_filter([
            isset($d['hr_pending_ot'])       ? "OT_HR:{$d['hr_pending_ot']}"       : null,
            isset($d['director_pending_ot']) ? "OT_DIR:{$d['director_pending_ot']}" : null,
            isset($d['pending_leaves'])      ? "CUTI:{$d['pending_leaves']}"        : null,
            isset($d['active_employees'])    ? "EMP:{$d['active_employees']}"       : null,
            isset($d['total_inventory'])     ? "INV:{$d['total_inventory']}"        : null,
            isset($d['low_stock'])           ? "LOW:{$d['low_stock']}"              : null,
            isset($d['pending_purchases'])   ? "PR:{$d['pending_purchases']}"       : null,
        ]));

        return <<<PROMPT
You are Symcore AI, assistant for Symcore internal system. Date: {$today}. User: {$name} (role: {$role}).

RULES:
- Reply in same language as user (Indonesian/English).
- ALWAYS call the right tool for specific data. Never say "I don't know" for data tools can fetch.
- For material/inventory questions (stok, category, lokasi, supplier) use cek_stok_material.
- For job order materials use get_job_order_materials, NOT get_material_requests.
- Never reveal salary, freight_price, budget, or sales to non-finance users.
- If tool returns access_denied, say access is restricted. If found:false, say data not found.
- Be concise. Use bullet lists for multiple items.

STATS: {$stats}
PROMPT;
    }
}
