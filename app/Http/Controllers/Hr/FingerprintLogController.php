<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\FingerprintLog;
use Illuminate\Http\Request;

class FingerprintLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:super_admin,admin_hr,admin');
    }

    public function index(Request $request)
    {
        $query = FingerprintLog::query()->latest();

        if ($request->filled('cloud_id')) {
            $query->where('cloud_id', 'like', '%' . $request->cloud_id . '%');
        }

        if ($request->filled('trans_id')) {
            $query->where('trans_id', 'like', '%' . $request->trans_id . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('event_time', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('event_time', '<=', $request->date_to);
        }

        $logs = $query->paginate(125)->withQueryString();

        return view('hr.fingerprint-logs.index', compact('logs'));
    }
}
