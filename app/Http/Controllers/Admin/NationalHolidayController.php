<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NationalHoliday;
use Illuminate\Http\Request;

class NationalHolidayController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->get('year', now()->year);
        $holidays = NationalHoliday::forYear($year)->orderBy('date')->get();
        $years = NationalHoliday::selectRaw('DISTINCT year')->orderBy('year', 'desc')->pluck('year');

        return view('admin.national-holidays.index', compact('holidays', 'year', 'years'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date'          => 'required|date|unique:national_holidays,date',
            'name'          => 'required|string|max:255',
            'is_joint_leave'=> 'boolean',
        ]);

        NationalHoliday::create([
            'date'          => $request->date,
            'name'          => $request->name,
            'year'          => date('Y', strtotime($request->date)),
            'is_joint_leave'=> $request->boolean('is_joint_leave'),
        ]);

        return back()->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function update(Request $request, NationalHoliday $nationalHoliday)
    {
        $request->validate([
            'date'          => 'required|date|unique:national_holidays,date,' . $nationalHoliday->id,
            'name'          => 'required|string|max:255',
            'is_joint_leave'=> 'boolean',
        ]);

        $nationalHoliday->update([
            'date'          => $request->date,
            'name'          => $request->name,
            'year'          => date('Y', strtotime($request->date)),
            'is_joint_leave'=> $request->boolean('is_joint_leave'),
        ]);

        return back()->with('success', 'Hari libur berhasil diperbarui.');
    }

    public function destroy(NationalHoliday $nationalHoliday)
    {
        $nationalHoliday->delete();
        return back()->with('success', 'Hari libur berhasil dihapus.');
    }
}
