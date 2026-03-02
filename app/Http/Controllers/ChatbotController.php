<?php

namespace App\Http\Controllers;

use App\Services\ChatbotContextService;
use App\Services\ChatbotToolService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatbotController extends Controller
{
    public function __construct(
        private ChatbotContextService $contextService,
        private ChatbotToolService $toolService,
    ) {}

    // =========================================================
    // TOOL DEFINITIONS (OpenAI function-calling format)
    // Setiap tool harus didaftarkan di sini DAN di executeTool()
    // =========================================================
    private function getTools(): array
    {
        return [
            $this->tool('cari_karyawan', 'Cari info karyawan (nama, posisi, dept, kontak, status)',
                ['nama' => 'Nama karyawan (sebagian nama)'], ['nama']),

            $this->tool('cek_saldo_cuti', 'Cek saldo & riwayat cuti karyawan',
                ['nama_karyawan' => 'Nama karyawan'], ['nama_karyawan']),

            $this->tool('get_leave_requests', 'Daftar permohonan cuti. Filter opsional: status (pending/approved/rejected), nama karyawan',
                ['status' => 'Filter status cuti', 'employee_name' => 'Filter nama karyawan'], []),

            $this->tool('get_overtime_requests', 'Daftar permohonan lembur. Filter opsional: status (draft/submitted/approved/rejected), nama karyawan',
                ['status' => 'Filter status lembur', 'employee_name' => 'Filter nama karyawan'], []),

            $this->tool('cek_stok_material', 'Cek stok/inventory material: jumlah, category, lokasi, satuan, supplier',
                ['nama_material' => 'Nama material (sebagian nama)'], ['nama_material']),

            $this->tool('get_material_requests', 'Daftar material request milik user (bukan per job order). Filter: status, keyword',
                ['status' => 'pending/approved/delivered/canceled', 'keyword' => 'Nama material atau proyek'], []),

            $this->tool('get_purchase_requests', 'Cari purchase request/PO berdasarkan nama material',
                ['keyword' => 'Nama material atau keyword'], ['keyword']),

            $this->tool('cari_proyek', 'Cari proyek berdasarkan nama, status, stage',
                ['keyword' => 'Nama atau keyword proyek'], ['keyword']),

            $this->tool('get_job_orders', 'Cari job order berdasarkan ID (JO-XXXXXX), nama, atau proyek',
                ['keyword' => 'ID atau nama job order'], ['keyword']),

            $this->tool('get_job_order_materials', 'Daftar material yang dibutuhkan untuk satu job order (by ID atau nama JO)',
                ['keyword' => 'ID atau nama job order'], ['keyword']),

            $this->tool('cek_status_pengiriman', 'Cek status pengiriman berdasarkan waybill atau freight company',
                ['keyword' => 'Nomor waybill atau nama freight company'], ['keyword']),
        ];
    }

    private function tool(string $name, string $description, array $props, array $required): array
    {
        $properties = [];
        foreach ($props as $key => $desc) {
            $properties[$key] = ['type' => 'string', 'description' => $desc];
        }
        return [
            'type'     => 'function',
            'function' => [
                'name'        => $name,
                'description' => $description,
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => $properties,
                    'required'   => $required,
                ],
            ],
        ];
    }

    // =========================================================
    // MAIN HANDLER
    // =========================================================
    public function message(Request $request)
    {
        $request->validate([
            'message'           => 'required|string|max:2000',
            'history'           => 'nullable|array|max:20',
            'history.*.role'    => 'required|in:user,assistant',
            'history.*.content' => 'required|string|max:4000',
        ]);

        $messages = [['role' => 'system', 'content' => $this->contextService->buildSystemPrompt()]];

        foreach (array_slice($request->history ?? [], -10) as $h) {
            $messages[] = ['role' => $h['role'], 'content' => $h['content']];
        }

        $messages[] = ['role' => 'user', 'content' => $request->message];

        try {
            // ── Round 1: kirim dengan tools ───────────────────
            $res1    = $this->callGroq($messages, $this->getTools());
            $data1   = $res1->json();
            $choice1 = $data1['choices'][0] ?? null;

            if (!$res1->successful() || !$choice1) {
                \Log::error('Chatbot Groq API error (round 1)', [
                    'status' => $res1->status(),
                    'body'   => $res1->body(),
                ]);
                return response()->json(['success' => false, 'reply' => 'API error. Please try again.']);
            }

            $aiMsg  = $choice1['message'];
            $reason = $choice1['finish_reason'] ?? '';

            // ── Tool call diminta ──────────────────────────────
            if ($reason === 'tool_calls' && !empty($aiMsg['tool_calls'])) {
                $messages[] = $aiMsg;

                foreach ($aiMsg['tool_calls'] as $tc) {
                    $toolName = $tc['function']['name'];
                    $args     = json_decode($tc['function']['arguments'] ?? '{}', true) ?? [];
                    $result   = $this->executeTool($toolName, $args);

                    $messages[] = [
                        'role'         => 'tool',
                        'tool_call_id' => $tc['id'],
                        'content'      => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                    ];
                }

                // ── Round 2: jawaban final (tanpa tools) ──────
                $res2  = $this->callGroq($messages);
                $reply = $res2->json('choices.0.message.content') ?? 'No response generated.';

                return response()->json(['success' => true, 'reply' => $reply]);
            }

            // ── Tidak perlu tool — jawaban langsung ───────────
            $reply = $aiMsg['content'] ?? 'No response generated.';
            return response()->json(['success' => true, 'reply' => $reply]);

        } catch (\Exception $e) {
            \Log::error('Chatbot exception', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'reply' => 'Connection error. Please try again.']);
        }
    }

    // =========================================================
    // GROQ API CALLER
    // =========================================================
    private function callGroq(array $messages, array $tools = [])
    {
        $payload = [
            'model'       => config('services.groq.model'),
            'messages'    => $messages,
            'max_tokens'  => 1024,
            'temperature' => 0.7,
        ];

        if (!empty($tools)) {
            $payload['tools']                = $tools;
            $payload['tool_choice']          = 'auto';
            $payload['parallel_tool_calls']  = false;
        }

        $client = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.groq.api_key'),
            'Content-Type'  => 'application/json',
        ])->timeout(60);

        // Fix SSL verification di local environment (Laragon/XAMPP Windows
        // tidak selalu punya CA bundle yang dikonfigurasi di php.ini)
        if (app()->environment('local')) {
            $client = $client->withOptions(['verify' => false]);
        }

        return $client->post(config('services.groq.url'), $payload);
    }

    // =========================================================
    // TOOL DISPATCHER
    // Setiap tool yang didaftarkan di getTools() harus ada di sini
    // =========================================================
    private function executeTool(string $name, array $args): array
    {
        return match ($name) {
            // HR
            'cari_karyawan'         => $this->toolService->cariKaryawan($args['nama'] ?? ''),
            'cek_saldo_cuti'        => $this->toolService->cekSaldoCuti($args['nama_karyawan'] ?? ''),
            'get_leave_requests'    => $this->toolService->getLeaveRequests($args['status'] ?? '', $args['employee_name'] ?? ''),
            'get_overtime_requests' => $this->toolService->getOvertimeRequests($args['status'] ?? '', $args['employee_name'] ?? ''),
            // Logistic / Material
            'cek_stok_material'     => $this->toolService->cekStokMaterial($args['nama_material'] ?? ''),
            'get_material_requests' => $this->toolService->getMaterialRequests($args['status'] ?? '', $args['keyword'] ?? ''),
            // Procurement
            'get_purchase_requests' => $this->toolService->getPurchaseRequests($args['keyword'] ?? ''),
            'cek_status_pengiriman' => $this->toolService->cekStatusPengiriman($args['keyword'] ?? ''),
            // Production / Project
            'cari_proyek'              => $this->toolService->cariProyek($args['keyword'] ?? ''),
            'get_job_orders'           => $this->toolService->getJobOrders($args['keyword'] ?? ''),
            'get_job_order_materials'  => $this->toolService->getJobOrderMaterials($args['keyword'] ?? ''),
            // Fallback
            default                 => ['error' => "Tool tidak dikenal: {$name}"],
        };
    }
}
