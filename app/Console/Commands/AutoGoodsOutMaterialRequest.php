<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Logistic\MaterialRequest;
use App\Models\Logistic\Inventory;
use App\Models\Logistic\GoodsOut;
use App\Helpers\MaterialUsageHelper;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AutoGoodsOutMaterialRequest extends Command
{
    // php artisan material-request:auto-goods-out
    protected $signature = 'material-request:auto-goods-out';
    protected $description = 'Auto Goods Out for approved Material Requests older than 2x24 hours';

    public function handle()
    {
        $now = Carbon::now();
        $deadline = $now->copy()->subHours(48);

        // Ambil semua material request yang sudah approve, belum delivered, dan sudah lebih dari 2x24 jam
        $requests = MaterialRequest::where('status', 'approved')->whereColumn('qty', '>', 'processed_qty')->where('approved_at', '<=', $deadline)->get();

        $count = 0;
        foreach ($requests as $req) {
            DB::beginTransaction();
            try {
                $inventory = Inventory::lockForUpdate()->find($req->inventory_id);
                $remainingQty = $req->qty - $req->processed_qty;

                // Cek stok cukup
                if ($remainingQty > 0 && $inventory && $inventory->quantity >= $remainingQty) {
                    // Kurangi stok
                    $inventory->quantity -= $remainingQty;
                    $inventory->save();

                    // Buat Goods Out
                    GoodsOut::create([
                        'material_request_id' => $req->id,
                        'inventory_id' => $req->inventory_id,
                        'project_id' => $req->project_id,
                        'requested_by' => $req->requested_by,
                        'quantity' => $remainingQty,
                        'remark' => '[AUTO] Goods Out by system after 2x24h',
                    ]);

                    // Update processed_qty dan status
                    $req->processed_qty += $remainingQty;
                    $req->status = 'delivered';
                    $req->save();

                    MaterialUsageHelper::sync($inventory->id, $req->project_id);

                    $count++;
                } else {
                    $this->info("SKIPPED: MR #{$req->id} - {$inventory?->name} (Project: {$req->project_id}) - Requested: {$remainingQty} {$inventory?->unit}, Available: {$inventory?->quantity} {$inventory?->unit}");
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('AutoGoodsOutMaterialRequest failed: ' . $e->getMessage());
            }
        }

        $this->info("Auto Goods Out processed for {$count} material requests.");
        return 0;
    }
}
