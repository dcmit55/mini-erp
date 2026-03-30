<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\SessionShift;
use App\Models\Admin\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionShiftController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!in_array(Auth::user()->role, ['super_admin', 'admin_hr', 'admin'])) {
                abort(403);
            }
            return $next($request);
        });
    }

    public function index()
    {
        $shifts = SessionShift::with('department')
            ->orderByRaw('department_id IS NULL DESC')
            ->orderBy('department_id')
            ->orderBy('for_wna')
            ->orderBy('start_time')
            ->get();

        $departments = Department::orderBy('name')->get();

        return view('hr.session-shifts.index', compact('shifts', 'departments'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        return view('hr.session-shifts.form', compact('departments'));
    }

    /**
     * Normalize all time fields to H:i (strip seconds if browser sends H:i:s).
     */
    private function normalizeTimeFields(Request $request): void
    {
        $timeFields = ['start_time', 'end_time', 'break_start', 'break_end', 'break2_start', 'break2_end', 'detect_from', 'detect_until'];
        foreach ($timeFields as $field) {
            $val = $request->input($field);
            if ($val && preg_match('/^\d{2}:\d{2}:\d{2}$/', $val)) {
                $request->merge([$field => substr($val, 0, 5)]);
            }
        }
    }

    public function store(Request $request)
    {
        $this->normalizeTimeFields($request);

        $data = $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'type_of_shift' => 'required|string|max:10',
            'start_time'    => 'required|date_format:H:i',
            'end_time'      => 'required|date_format:H:i',
            'break_start'   => 'nullable|date_format:H:i',
            'break_end'     => 'nullable|date_format:H:i',
            'break2_start'  => 'nullable|date_format:H:i',
            'break2_end'    => 'nullable|date_format:H:i',
            'for_wna'       => 'boolean',
            'detect_from'   => 'required|date_format:H:i',
            'detect_until'  => 'required|date_format:H:i',
            'is_active'     => 'boolean',
        ]);

        $data['for_wna']   = $request->boolean('for_wna');
        $data['is_active'] = $request->boolean('is_active', true);

        SessionShift::create($data);

        return redirect()->route('session-shifts.index')
            ->with('success', 'Shift created successfully.');
    }

    public function edit(SessionShift $sessionShift)
    {
        $departments = Department::orderBy('name')->get();
        return view('hr.session-shifts.form', ['shift' => $sessionShift, 'departments' => $departments]);
    }

    public function update(Request $request, SessionShift $sessionShift)
    {
        $this->normalizeTimeFields($request);

        $data = $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'type_of_shift' => 'required|string|max:10',
            'start_time'    => 'required|date_format:H:i',
            'end_time'      => 'required|date_format:H:i',
            'break_start'   => 'nullable|date_format:H:i',
            'break_end'     => 'nullable|date_format:H:i',
            'break2_start'  => 'nullable|date_format:H:i',
            'break2_end'    => 'nullable|date_format:H:i',
            'for_wna'       => 'boolean',
            'detect_from'   => 'required|date_format:H:i',
            'detect_until'  => 'required|date_format:H:i',
            'is_active'     => 'boolean',
        ]);

        $data['for_wna']   = $request->boolean('for_wna');
        $data['is_active'] = $request->boolean('is_active', true);

        $sessionShift->update($data);

        return redirect()->route('session-shifts.index')
            ->with('success', 'Shift updated successfully.');
    }

    public function destroy(SessionShift $sessionShift)
    {
        $sessionShift->delete();
        return redirect()->route('session-shifts.index')
            ->with('success', 'Shift deleted.');
    }

    public function clearBreak2(SessionShift $sessionShift)
    {
        $sessionShift->update([
            'break2_start' => null,
            'break2_end'   => null,
        ]);

        return redirect()->route('session-shifts.index')
            ->with('success', "Break 2 cleared for shift {$sessionShift->type_of_shift}.");
    }
}
