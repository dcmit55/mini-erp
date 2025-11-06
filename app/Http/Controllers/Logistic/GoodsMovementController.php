<?php

namespace App\Http\Controllers\Logistic;

use App\Http\Controllers\Controller;
use App\Models\Logistic\GoodsMovement;
use App\Models\Logistic\GoodsMovementItem;
use App\Models\Logistic\Inventory;
use App\Models\Logistic\Goodsin;
use App\Models\Admin\Department;
use App\Models\Production\Project;
use App\Models\Procurement\GoodsReceive;
use App\Models\Procurement\GoodsReceiveDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class GoodsMovementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getDataTablesData($request);
        }

        $departments = Department::orderBy('name')->get();

        $today = now()->toDateString();
        $todayMovements = GoodsMovement::whereDate('movement_date', $today)->count();
        $pendingCount = GoodsMovement::where('status', 'Pending')->count();
        $thisWeekCount = GoodsMovement::whereBetween('movement_date', [now()->startOfWeek(), now()->endOfWeek()])->count();

        return view('logistic.goods_movement.index', compact('departments', 'todayMovements', 'pendingCount', 'thisWeekCount'));
    }

    public function getDataTablesData(Request $request)
    {
        $query = GoodsMovement::with(['department', 'items', 'creator'])->latest();

        if ($request->filled('department_filter')) {
            $query->where('department_id', $request->department_filter);
        }

        if ($request->filled('origin_filter')) {
            $query->where('origin', $request->origin_filter);
        }

        if ($request->filled('status_filter')) {
            $query->where('status', $request->status_filter);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('movement_date', function ($row) {
                return $row->movement_date->format('d M Y');
            })
            ->addColumn('movement_type', function ($row) {
                $typeClass = $row->movement_type === 'Handcarry' ? 'primary' : 'warning';
                return "<span class='badge bg-{$typeClass}'>{$row->movement_type}</span>";
            })
            ->addColumn('goods_type', function ($row) {
                $types = $row->items->pluck('material_type')->unique()->filter()->join(', ');
                return $types ?: '-';
            })
            ->addColumn('destination', function ($row) {
                $colors = [
                    'SG' => 'success',
                    'BT' => 'info',
                    'CN' => 'danger',
                    'Other' => 'secondary',
                ];
                $color = $colors[$row->destination] ?? 'secondary';
                return "<span class='badge bg-{$color}'>{$row->destination}</span>";
            })
            // ✅ TAMBAHAN: Kolom Sender Status
            ->addColumn('sender_status', function ($row) {
                $statusColors = [
                    'Pending' => 'secondary',
                    'Prepared' => 'info',
                    'Sent by Handcarry' => 'warning',
                    'Sent by Shipping' => 'warning',
                    'Checked' => 'success',
                    'Received' => 'success',
                ];
                $currentStatus = $row->sender_status ?? 'Pending';
                $color = $statusColors[$currentStatus] ?? 'secondary';

                return "
                    <div class='dropdown' style='display:inline-block;'>
                        <button class='btn btn-sm btn-{$color} dropdown-toggle' type='button'
                            id='senderDropdown{$row->id}' data-bs-toggle='dropdown' aria-expanded='false'
                            style='width: 140px; text-align: left;'>
                            {$currentStatus}
                        </button>
                        <ul class='dropdown-menu dropdown-menu-sm' aria-labelledby='senderDropdown{$row->id}'>
                            <li><a class='dropdown-item' href='#' onclick='updateStatus({$row->id}, \"sender_status\", \"Pending\")'>Pending</a></li>
                            <li><a class='dropdown-item' href='#' onclick='updateStatus({$row->id}, \"sender_status\", \"Prepared\")'>Prepared</a></li>
                            <li><a class='dropdown-item' href='#' onclick='updateStatus({$row->id}, \"sender_status\", \"Sent by Handcarry\")'>Sent by Handcarry</a></li>
                            <li><a class='dropdown-item' href='#' onclick='updateStatus({$row->id}, \"sender_status\", \"Sent by Shipping\")'>Sent by Shipping</a></li>
                            <li><a class='dropdown-item' href='#' onclick='updateStatus({$row->id}, \"sender_status\", \"Checked\")'>Checked</a></li>
                            <li><a class='dropdown-item' href='#' onclick='updateStatus({$row->id}, \"sender_status\", \"Received\")'>Received</a></li>
                        </ul>
                    </div>
                ";
            })

            ->addColumn('receiver_status', function ($row) {
                $statusColors = [
                    'Pending' => 'secondary',
                    'Prepared' => 'info',
                    'Sent by Handcarry' => 'warning',
                    'Sent by Shipping' => 'warning',
                    'Checked' => 'success',
                    'Received' => 'success',
                ];
                $currentStatus = $row->receiver_status ?? 'Pending';
                $color = $statusColors[$currentStatus] ?? 'secondary';

                return "
                    <div class='dropdown' style='display:inline-block;'>
                        <button class='btn btn-sm btn-{$color} dropdown-toggle' type='button'
                            id='receiverDropdown{$row->id}' data-bs-toggle='dropdown' aria-expanded='false'
                            style='width: 140px; text-align: left;'>
                            {$currentStatus}
                        </button>
                        <ul class='dropdown-menu dropdown-menu-sm' aria-labelledby='receiverDropdown{$row->id}'>
                            <li><a class='dropdown-item' href='#' onclick='updateStatus({$row->id}, \"receiver_status\", \"Pending\")'>Pending</a></li>
                            <li><a class='dropdown-item' href='#' onclick='updateStatus({$row->id}, \"receiver_status\", \"Prepared\")'>Prepared</a></li>
                            <li><a class='dropdown-item' href='#' onclick='updateStatus({$row->id}, \"receiver_status\", \"Sent by Handcarry\")'>Sent by Handcarry</a></li>
                            <li><a class='dropdown-item' href='#' onclick='updateStatus({$row->id}, \"receiver_status\", \"Sent by Shipping\")'>Sent by Shipping</a></li>
                            <li><a class='dropdown-item' href='#' onclick='updateStatus({$row->id}, \"receiver_status\", \"Checked\")'>Checked</a></li>
                            <li><a class='dropdown-item' href='#' onclick='updateStatus({$row->id}, \"receiver_status\", \"Received\")'>Received</a></li>
                        </ul>
                    </div>
                ";
            })
            ->editColumn('status', function ($row) {
                $statusClass = $row->status === 'Received' ? 'success' : 'warning';
                return "
                    <span class='badge bg-{$statusClass} status-badge cursor-pointer'
                        data-id='{$row->id}'
                        data-status='{$row->status}'>
                        {$row->status}
                    </span>
                ";
            })
            ->editColumn('origin', function ($row) {
                $colors = [
                    'SG' => 'primary',
                    'BT' => 'info',
                    'CN' => 'danger',
                    'Other' => 'secondary',
                ];
                $color = $colors[$row->origin] ?? 'secondary';
                return "<span class='badge bg-{$color}'>{$row->origin}</span>";
            })
            ->addColumn('total_items', function ($row) {
                return $row->items->count();
            })
            ->addColumn('total_quantity', function ($row) {
                return number_format($row->items->sum('quantity'), 2);
            })
            ->addColumn('department', function ($row) {
                return $row->department->name ?? '-';
            })
            ->addColumn('actions', function ($row) {
                return "
                    <div class='btn-group btn-group-sm' role='group'>
                        <a href='" .
                    route('goods-movement.show', $row->id) .
                    "'
                            class='btn btn-info btn-sm' title='View'>
                            <i class='bi bi-eye'></i>
                        </a>
                        <a href='" .
                    route('goods-movement.edit', $row->id) .
                    "'
                            class='btn btn-warning btn-sm' title='Edit'>
                            <i class='bi bi-pencil'></i>
                        </a>
                        <form method='POST' action='" .
                    route('goods-movement.destroy', $row->id) .
                    "'
                            style='display:inline;'>
                            " .
                    csrf_field() .
                    "
                            " .
                    method_field('DELETE') .
                    "
                            <button type='submit' class='btn btn-danger btn-sm'
                                onclick='return confirm(\"Are you sure?\")' title='Delete'>
                                <i class='bi bi-trash'></i>
                            </button>
                        </form>
                    </div>
                ";
            })
            ->rawColumns(['status', 'origin', 'movement_type', 'destination', 'sender_status', 'receiver_status', 'actions'])
            ->make(true);
    }

    // ✅ TAMBAHAN: Tambah method untuk update status sender/receiver
    public function updateSenderReceiverStatus(Request $request, GoodsMovement $goods_movement)
    {
        $request->validate([
            'status_type' => 'required|in:sender_status,receiver_status',
            'status_value' => 'required|in:Pending,Prepared,Sent by Handcarry,Sent by Shipping,Checked,Received',
        ]);

        $goods_movement->update([
            $request->status_type => $request->status_value,
        ]);

        return response()->json(['success' => true, 'message' => 'Status updated successfully']);
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        $materials = Inventory::orderBy('name')->get();

        return view('logistic.goods_movement.create', compact('departments', 'materials'));
    }

    public function store(Request $request)
    {
        // ✅ Validasi request
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'movement_date' => 'required|date',
            'movement_type' => 'required|in:Handcarry,Courier',
            'movement_type_value' => 'required|string|max:255',
            'origin' => 'required|in:SG,BT,CN,Other',
            'destination' => 'required|in:SG,BT,CN,Other',
            'sender' => 'required|string|max:255',
            'receiver' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_type' => 'required|in:Project,Goods Receive,Restock,New Material',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:50', // ✅ UBAH: Dari required menjadi nullable
            // Conditional validations
            'items.*.project_id' => 'nullable|exists:projects,id',
            'items.*.goods_receive_id' => 'nullable|exists:goods_receives,id',
            'items.*.goods_receive_detail_id' => 'nullable|exists:goods_receive_details,id',
            'items.*.inventory_id' => 'nullable|exists:inventories,id',
            'items.*.new_material_name' => 'nullable|string|max:255',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // ✅ Simpan Goods Movement
            $movement = GoodsMovement::create([
                'department_id' => $request->department_id,
                'movement_date' => $request->movement_date,
                'movement_type' => $request->movement_type,
                'movement_type_value' => $request->movement_type_value,
                'origin' => $request->origin,
                'destination' => $request->destination,
                'sender' => $request->sender,
                'receiver' => $request->receiver,
                'status' => 'Pending',
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            // ✅ Loop items dan simpan
            foreach ($request->items as $itemIndex => $item) {
                if (empty($item['material_type'])) {
                    continue;
                }

                $materialType = $item['material_type'];

                // ✅ Inisialisasi dengan NULL untuk semua field
                $itemData = [
                    'goods_movement_id' => $movement->id,
                    'material_type' => $materialType,
                    'quantity' => floatval($item['quantity']),
                    'unit' => $item['unit'] ?? 'pcs',
                    'notes' => $item['notes'] ?? null,
                    'inventory_id' => null,
                    'project_id' => null,
                    'goods_receive_id' => null,
                    'goods_receive_detail_id' => null,
                    'new_material_name' => null,
                ];

                // ✅ Set field sesuai material type
                switch ($materialType) {
                    case 'Project':
                        if (empty($item['project_id'])) {
                            throw new \Exception('Row ' . ($itemIndex + 1) . ': Project harus dipilih');
                        }
                        $itemData['project_id'] = intval($item['project_id']);
                        break;

                    case 'Goods Receive':
                        // ✅ VALIDASI KETAT: Kedua field HARUS ada
                        if (empty($item['goods_receive_id'])) {
                            throw new \Exception('Row ' . ($itemIndex + 1) . ': Goods Receive harus dipilih');
                        }
                        if (empty($item['goods_receive_detail_id'])) {
                            throw new \Exception('Row ' . ($itemIndex + 1) . ': Goods Receive Item harus dipilih');
                        }

                        $itemData['goods_receive_id'] = intval($item['goods_receive_id']);
                        $itemData['goods_receive_detail_id'] = intval($item['goods_receive_detail_id']);
                        break;

                    case 'Restock':
                        if (empty($item['inventory_id'])) {
                            throw new \Exception('Row ' . ($itemIndex + 1) . ': Material harus dipilih');
                        }
                        $itemData['inventory_id'] = intval($item['inventory_id']);
                        break;

                    case 'New Material':
                        if (empty($item['new_material_name'])) {
                            throw new \Exception('Row ' . ($itemIndex + 1) . ': Nama material baru harus diisi');
                        }
                        $itemData['new_material_name'] = trim($item['new_material_name']);
                        break;
                }

                // ✅ Log untuk debugging
                \Log::info('Saving item row ' . ($itemIndex + 1) . ':', $itemData);

                // ✅ Simpan item
                GoodsMovementItem::create($itemData);
            }

            DB::commit();
            return redirect()->route('goods-movement.index')->with('success', 'Goods Movement berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error storing goods movement: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('Request items: ' . json_encode($request->items));

            return back()
                ->withInput()
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function show(GoodsMovement $goods_movement)
    {
        $goods_movement->load(['department', 'items.inventory', 'items.project', 'items.goodsReceiveDetail', 'creator']);

        return view('logistic.goods_movement.show', compact('goods_movement'));
    }

    public function edit(GoodsMovement $goods_movement)
    {
        $goods_movement->load('items');
        $departments = Department::orderBy('name')->get();
        $materials = Inventory::orderBy('name')->get();

        return view('logistic.goods_movement.edit', compact('goods_movement', 'departments', 'materials'));
    }

    public function update(Request $request, GoodsMovement $goods_movement)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'movement_date' => 'required|date',
            'movement_type' => 'required|in:Handcarry,Courier',
            'movement_type_value' => 'required|string|max:255',
            'origin' => 'required|in:SG,BT,CN,Other',
            'destination' => 'required|in:SG,BT,CN,Other',
            'sender' => 'required|string|max:255',
            'receiver' => 'required|string|max:255',
            'status' => 'required|in:Pending,Received',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.inventory_id' => 'required|exists:inventories,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $goods_movement->update([
                'department_id' => $request->department_id,
                'movement_date' => $request->movement_date,
                'movement_type' => $request->movement_type,
                'movement_type_value' => $request->movement_type_value,
                'origin' => $request->origin,
                'destination' => $request->destination,
                'sender' => $request->sender,
                'receiver' => $request->receiver,
                'status' => $request->status,
                'notes' => $request->notes,
            ]);

            $goods_movement->items()->delete();

            foreach ($request->items as $item) {
                GoodsMovementItem::create([
                    'goods_movement_id' => $goods_movement->id,
                    'inventory_id' => $item['inventory_id'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();
            return redirect()->route('goods-movement.index')->with('success', 'Goods Movement updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating goods movement: ' . $e->getMessage());
        }
    }

    public function destroy(GoodsMovement $goods_movement)
    {
        $goods_movement->delete();

        return redirect()->route('goods-movement.index')->with('success', 'Goods Movement deleted successfully!');
    }

    public function updateStatus(Request $request, GoodsMovement $goods_movement)
    {
        $request->validate([
            'status' => 'required|in:Pending,Received',
        ]);

        $goods_movement->update(['status' => $request->status]);

        return response()->json(['success' => true, 'message' => 'Status updated']);
    }

    public function getMovementTypeValues(Request $request)
    {
        $type = $request->query('type');
        $values = GoodsMovement::getMovementTypeValues($type);

        return response()->json(['values' => $values]);
    }

    public function getProjects()
    {
        $projects = Project::orderBy('name')->get(['id', 'name']);
        return response()->json(['projects' => $projects]);
    }

    public function getGoodsReceives()
    {
        $goodsReceives = GoodsReceive::orderBy('created_at', 'desc')->get(['id', 'international_waybill_no', 'created_at']);

        return response()->json(['goodsReceives' => $goodsReceives]);
    }

    public function getGoodsReceiveItems(Request $request)
    {
        $goodsReceiveId = $request->query('goods_receive_id');

        if (!$goodsReceiveId) {
            return response()->json(['items' => []]);
        }

        $items = GoodsReceiveDetail::where('goods_receive_id', $goodsReceiveId)->get(['id', 'material_name', 'received_qty', 'purchase_type', 'project_name']);

        return response()->json(['items' => $items]);
    }

    public function parseWhatsApp(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $message = $request->message;
        $items = [];

        $patterns = ['/([^*\n]+)\s*\*\s*(\d+(?:\.\d+)?)\s*\*\s*(\w+)\s*\*\s*([^\n]*)/i', '/([^*\n]+)\s*\*\s*(\d+(?:\.\d+)?)\s*\*\s*(\w+)(?:\s*$|\s*\n)/i', '/([^-\n]+)\s*-\s*(\d+(?:\.\d+)?)\s*-\s*(\w+)(?:\s*$|\s*\n)/i'];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $message, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $itemName = trim($match[1]);
                    $qty = (float) $match[2];
                    $unit = isset($match[3]) ? trim($match[3]) : 'pcs';
                    $notes = isset($match[4]) ? trim($match[4]) : '';

                    if (empty($itemName)) {
                        continue;
                    }

                    $inventory = Inventory::where('name', 'like', '%' . $itemName . '%')->first();

                    if ($inventory) {
                        $items[] = [
                            'name' => $inventory->name,
                            'inventory_id' => $inventory->id,
                            'quantity' => $qty,
                            'unit' => $unit ?: $inventory->unit ?? 'pcs',
                            'notes' => $notes,
                        ];
                    }
                }
                break;
            }
        }

        return response()->json([
            'items' => $items,
            'count' => count($items),
        ]);
    }

    public function export(Request $request)
    {
        $query = GoodsMovement::with(['department', 'items.inventory']);

        if ($request->filled('department_filter')) {
            $query->where('department_id', $request->department_filter);
        }

        if ($request->filled('origin_filter')) {
            $query->where('origin', $request->origin_filter);
        }

        if ($request->filled('status_filter')) {
            $query->where('status', $request->status_filter);
        }

        $movements = $query->get();

        $csv = "Movement Date,Department,Movement Type,Type Value,Origin,Destination,Sender,Receiver,Status,Material,Quantity,Unit,Notes\n";

        foreach ($movements as $movement) {
            foreach ($movement->items as $item) {
                $materialName = $item->inventory?->name ?? ($item->new_material_name ?? '-');

                $csv .= sprintf('"%s","%s","%s","%s","%s","%s","%s","%s","%s","%s",%s,"%s","%s"' . "\n", $movement->movement_date->format('Y-m-d'), $movement->department->name, $movement->movement_type, $movement->movement_type_value, $movement->origin, $movement->destination, $movement->sender, $movement->receiver, $movement->status, $materialName, $item->quantity, $item->unit, $movement->notes ?? '');
            }
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="goods_movement_' . now()->format('Y-m-d') . '.csv"',
        ]);
    }
    public function transferToInventory(Request $request, $itemId)
    {
        try {
            DB::beginTransaction();

            $item = GoodsMovementItem::with(['goodsMovement', 'inventory'])->findOrFail($itemId);

            // Cek apakah sudah ditransfer
            if ($item->transferred_to_inventory) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Item sudah ditransfer sebelumnya',
                    ],
                    400,
                );
            }

            // Tentukan nama material
            $materialName = null;
            $inventoryId = null;

            if ($item->material_type === 'Goods Receive' && $item->goodsReceiveDetail) {
                $materialName = $item->goodsReceiveDetail->material_name;
            } elseif ($item->material_type === 'New Material') {
                $materialName = $item->new_material_name;
            } elseif ($item->material_type === 'Restock' && $item->inventory) {
                $materialName = $item->inventory->name;
                $inventoryId = $item->inventory_id;
            } elseif ($item->material_type === 'Project' && $item->project) {
                // Untuk project, gunakan nama project sebagai material name
                $materialName = 'Project: ' . $item->project->name;
            }

            if (!$materialName) {
                throw new \Exception('Material name tidak ditemukan');
            }

            // Jika sudah ada inventory_id (Restock), update quantity
            if ($inventoryId) {
                $inventory = Inventory::find($inventoryId);

                // Update quantity inventory
                $inventory->increment('quantity', $item->quantity);

                // ✅ Catat di goods_in dengan field yang benar
                $goodsIn = GoodsIn::create([
                    'inventory_id' => $inventoryId,
                    'quantity' => $item->quantity,
                    'returned_by' => Auth::user()->username ?? 'System Transfer', // ✅ TAMBAH
                    'returned_at' => now(), // ✅ TAMBAH
                    'remark' => 'From goods movement transfer (Movement Date: ' . $item->goodsMovement->movement_date->format('d M Y') . ')',
                ]);
            } else {
                // BAGIAN 2: Jika belum ada, cari atau buat inventory baru
                $inventory = Inventory::where('name', $materialName)->first();

                if (!$inventory) {
                    // Buat inventory baru
                    $inventory = Inventory::create([
                        'name' => $materialName,
                        'quantity' => $item->quantity,
                        'unit' => $item->unit,
                        'remark' => 'From goods movement transfer',
                        'category_id' => null,
                        'location_id' => null,
                    ]);
                } else {
                    // Update quantity jika sudah ada
                    $inventory->increment('quantity', $item->quantity);
                }

                // ✅ Catat di goods_in dengan field yang benar
                $goodsIn = GoodsIn::create([
                    'inventory_id' => $inventory->id,
                    'quantity' => $item->quantity,
                    'returned_by' => Auth::user()->username ?? 'System Transfer', // ✅ TAMBAH
                    'returned_at' => now(), // ✅ TAMBAH
                    'remark' => 'From goods movement transfer (Movement Date: ' . $item->goodsMovement->movement_date->format('d M Y') . ')',
                ]);
            }
            // Update status transfer
            $item->update([
                'transferred_to_inventory' => true,
                'transferred_at' => now(),
                'transferred_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '',
                'data' => [
                    'item_id' => $item->id,
                    'inventory_id' => $inventory->id,
                    'inventory_name' => $inventory->name,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error transferring to inventory: ' . $e->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }
}
