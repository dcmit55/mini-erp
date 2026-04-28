<?php

namespace App\Http\Controllers\Logistic;

use App\Http\Controllers\Controller;
use App\Models\Logistic\Inventory;
use App\Models\Logistic\InventoryBatch;
use App\Models\Logistic\StockAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class StockAdjustmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:logistic.stock-adjustment.view');
    }

    // ─── Index ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = StockAdjustment::with(['inventory:id,name,material_code,currency_id,unit_id', 'inventory.currency:id,name', 'inventory.unit:id,name', 'batch:id,batch_number,unit_price,currency_id', 'batch.currency:id,name', 'creator:id,username'])->select('stock_adjustments.*');

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }
            if ($request->filled('inventory_id')) {
                $query->where('inventory_id', $request->inventory_id);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('material_name', fn($row) => optional($row->inventory)->name ?? '—')
                ->addColumn('material_code', fn($row) => optional($row->inventory)->material_code ?? '—')
                ->addColumn('batch_number', fn($row) => optional($row->batch)->batch_number ?? '<em class="text-muted">New Batch</em>')
                ->addColumn('type_badge', function ($row) {
                    $label = StockAdjustment::typeLabel($row->type);
                    $cls = StockAdjustment::typeBadgeClass($row->type);
                    return '<span class="badge ' . $cls . '">' . e($label) . '</span>';
                })
                ->addColumn('qty_display', function ($row) {
                    $qty = (float) $row->qty;
                    // Format: trim trailing zeros, max 2 decimal places
                    $formatted = rtrim(rtrim(number_format($qty, 2, '.', ','), '0'), '.');
                    if ($qty > 0) {
                        return '<span class="text-success fw-semibold">+' . $formatted . '</span>';
                    }
                    return '<span class="text-danger fw-semibold">' . $formatted . '</span>';
                })
                ->addColumn('price_display', function ($row) {
                    if (!is_null($row->price) && (float) $row->price > 0) {
                        // Use batch currency if available, else inventory currency
                        $currency = optional($row->batch)->currency ?? optional($row->inventory)->currency;
                        $currCode = strtoupper($currency->name ?? 'IDR');
                        return $currCode . ' ' . number_format((float) $row->price, 2, '.', ',');
                    }
                    return '<em class="text-muted">—</em>';
                })
                ->addColumn('creator_name', fn($row) => optional($row->creator)->username ?? '—')
                ->addColumn('formatted_date', fn($row) => $row->created_at->format('d M Y H:i'))
                ->addColumn('actions', function ($row) {
                    $showUrl = route('stock-adjustments.show', $row->id);
                    $againUrl = route('stock-adjustments.create') . '?inventory_id=' . $row->inventory_id . '&batch_id=' . ($row->batch_id ?? '') . '&type=' . ($row->type === 'initial_stock' ? 'adjustment' : $row->type);
                    $canWrite = Auth::user()->can('logistic.stock-adjustment.create');
                    $btn = '<a href="' . $showUrl . '" class="btn btn-sm btn-outline-info" title="Detail"><i class="bi bi-eye"></i></a>';
                    if ($canWrite) {
                        $btn .= ' <a href="' . $againUrl . '" class="btn btn-sm btn-outline-warning" title="Adjust Again"><i class="bi bi-arrow-repeat"></i></a>';
                    }
                    return $btn;
                })
                ->rawColumns(['batch_number', 'type_badge', 'qty_display', 'price_display', 'actions'])
                ->make(true);
        }

        $inventories = Inventory::orderBy('name')->get(['id', 'name', 'material_code']);
        return view('logistic.stock_adjustments.index', compact('inventories'));
    }

    // ─── Create form ─────────────────────────────────────────────────────────

    public function create()
    {
        $this->authorizeWrite();

        $inventories = Inventory::orderBy('name')->get(['id', 'name', 'material_code', 'unit']);
        $projects = \App\Models\Production\Project::notArchived()
            ->orderBy('name')
            ->get(['id', 'name']);
        $currencies = \App\Models\Finance\Currency::orderBy('name')->get(['id', 'name']);
        return view('logistic.stock_adjustments.create', compact('inventories', 'projects', 'currencies'));
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $this->authorizeWrite();

        $isCorrection = $request->input('type') === 'correction';
        $isInitialStock = $request->input('type') === 'initial_stock';
        $validated = $request->validate(
            [
                'inventory_id' => ['required', 'exists:inventories,id'],
                'project_id' => ['nullable', 'exists:projects,id'],
                'type' => ['required', 'in:initial_stock,adjustment,correction'],
                'qty' => $isCorrection
                    ? ['required', 'numeric', 'min:0'] // Correction: SET to value, 0 allowed
                    : ['required', 'numeric', 'not_in:0'], // Adjustment/Initial: delta, 0 meaningless
                'price' => $isInitialStock
                    ? ['required', 'numeric', 'min:0.0001'] // Initial Stock: price mandatory
                    : ['nullable', 'numeric', 'min:0'], // Adjustment: price optional (revaluation)
                'currency_id' => $isInitialStock ? ['required', 'exists:currencies,id'] : ['nullable', 'exists:currencies,id'],
                'batch_id' => ['nullable', 'exists:inventory_batches,id'],
                'reason' => ['nullable', 'string', 'max:1000'],
            ],
            [
                'qty.not_in' => 'Quantity tidak boleh 0.',
                'price.required' => 'Unit Price wajib diisi untuk tipe Initial Stock.',
                'price.min' => 'Unit Price harus lebih dari 0 untuk tipe Initial Stock.',
            ],
        );

        // Adjustment & Correction require an existing batch
        if (in_array($validated['type'], ['adjustment', 'correction']) && empty($validated['batch_id'])) {
            return back()
                ->withInput()
                ->withErrors(['batch_id' => 'Batch wajib dipilih untuk tipe Adjustment / Correction.']);
        }

        DB::beginTransaction();
        try {
            $inventory = Inventory::findOrFail($validated['inventory_id']);
            $qty = (float) $validated['qty'];

            if ($validated['type'] === StockAdjustment::TYPE_INITIAL_STOCK) {
                // Create a new batch
                DB::select('SELECT GET_LOCK(?, 5) as l', ['inventory_batch_number_lock']);
                try {
                    $batchNumber = InventoryBatch::generateBatchNumber($inventory->id);
                    $batch = InventoryBatch::create([
                        'batch_number' => $batchNumber,
                        'inventory_id' => $inventory->id,
                        'qty' => $qty,
                        'qty_remaining' => $qty,
                        'unit_price' => (float) ($validated['price'] ?? 0),
                        'currency_id' => (int) ($validated['currency_id'] ?? ($inventory->currency_id ?? 6)),
                        'received_date' => now()->toDateString(),
                        'source_type' => InventoryBatch::SOURCE_INITIAL_STOCK,
                        'notes' => $validated['reason'],
                    ]);
                } finally {
                    DB::select('SELECT RELEASE_LOCK(?)', ['inventory_batch_number_lock']);
                }

                $adjustment = StockAdjustment::create([
                    'inventory_id' => $inventory->id,
                    'batch_id' => $batch->id,
                    'project_id' => $validated['project_id'] ?? null,
                    'type' => $validated['type'],
                    'qty' => $qty,
                    'price' => (float) ($validated['price'] ?? 0),
                    'reason' => $validated['reason'],
                    'created_by' => Auth::id(),
                ]);
            } else {
                // Adjustment or Correction — modify existing batch qty_remaining
                $batch = InventoryBatch::where('id', $validated['batch_id'])->lockForUpdate()->first();

                if (!$batch) {
                    DB::rollBack();
                    return back()
                        ->withInput()
                        ->withErrors(['batch_id' => 'Batch tidak ditemukan.']);
                }

                // Validate ownership — cast to int to avoid strict type mismatch between string/int from PDO
                if ((int) $batch->inventory_id !== (int) $inventory->id) {
                    DB::rollBack();
                    return back()
                        ->withInput()
                        ->withErrors(['batch_id' => 'Batch tidak cocok dengan material yang dipilih.']);
                }

                if ($validated['type'] === StockAdjustment::TYPE_CORRECTION) {
                    // Correction = SET qty_remaining to exact value (must be >= 0)
                    $newRemaining = $qty;
                    if ($newRemaining < 0) {
                        DB::rollBack();
                        return back()
                            ->withInput()
                            ->withErrors(['qty' => 'Nilai koreksi tidak boleh negatif.']);
                    }
                } else {
                    // Adjustment = DELTA (+/-)
                    $newRemaining = (float) $batch->qty_remaining + $qty;
                    if ($newRemaining < 0) {
                        DB::rollBack();
                        return back()
                            ->withInput()
                            ->withErrors([
                                'qty' => 'Qty deduction melebihi stok tersisa pada batch ini (' . number_format($batch->qty_remaining, 4) . ').',
                            ]);
                    }
                }

                $batch->update(['qty_remaining' => $newRemaining]);

                // Optionally update unit_price and/or currency if provided
                $batchUpdates = [];
                if (!is_null($validated['price']) && (float) $validated['price'] > 0) {
                    $batchUpdates['unit_price'] = (float) $validated['price'];
                }
                if (!empty($validated['currency_id'])) {
                    $batchUpdates['currency_id'] = (int) $validated['currency_id'];
                }
                if (!empty($batchUpdates)) {
                    $batch->update($batchUpdates);
                }

                $adjustment = StockAdjustment::create([
                    'inventory_id' => $inventory->id,
                    'batch_id' => $batch->id,
                    'project_id' => $validated['project_id'] ?? null,
                    'type' => $validated['type'],
                    'qty' => $qty,
                    'price' => !is_null($validated['price']) ? (float) $validated['price'] : null,
                    'reason' => $validated['reason'],
                    'created_by' => Auth::id(),
                ]);
            }

            DB::commit();

            Log::info('Stock adjustment created', [
                'adjustment_id' => $adjustment->id,
                'inventory_id' => $inventory->id,
                'type' => $adjustment->type,
                'qty' => $qty,
                'by' => Auth::id(),
            ]);

            return redirect()->route('stock-adjustments.index')->with('success', 'Stock adjustment berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock adjustment failed', ['error' => $e->getMessage()]);
            return back()
                ->withInput()
                ->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    // ─── Show ─────────────────────────────────────────────────────────────────

    public function show(StockAdjustment $stockAdjustment)
    {
        $stockAdjustment->load(['inventory.batches', 'batch', 'creator:id,username']);
        return view('logistic.stock_adjustments.show', compact('stockAdjustment'));
    }

    // ─── AJAX: get batches for a given inventory ──────────────────────────────

    public function getBatches(Request $request)
    {
        $request->validate(['inventory_id' => ['required', 'integer', 'exists:inventories,id']]);

        // Load inventory unit for the unit label
        $inventory = Inventory::with(['unit:id,name', 'currency:id,name'])->findOrFail($request->inventory_id);

        $batches = InventoryBatch::where('inventory_id', $request->inventory_id)
            ->whereNull('deleted_at')
            ->with('currency:id,name')
            ->orderBy('received_date')
            ->orderBy('id')
            ->get(['id', 'batch_number', 'qty_remaining', 'unit_price', 'currency_id']);

        $unitName = $inventory->unit_name ?: 'pcs';
        $inventoryCurrencyCode = strtoupper(optional($inventory->currency)->name ?? 'IDR');

        return response()->json([
            'unit' => $unitName,
            'currency' => $inventoryCurrencyCode,
            'batches' => $batches->map(
                fn($b) => [
                    'id' => $b->id,
                    'batch_number' => $b->batch_number,
                    'qty_remaining' => (float) $b->qty_remaining,
                    'unit_price' => (float) $b->unit_price,
                    'currency' => strtoupper(optional($b->currency)->name ?? $inventoryCurrencyCode),
                ],
            ),
        ]);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function authorizeWrite(): void
    {
        abort_unless(Auth::user()->can('logistic.stock-adjustment.create'), 403, 'You do not have permission to create stock adjustments.');
    }
}
