<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->integer('year', now()->year);

        return view('admin.master.holidays', ['holidays' => Holiday::query()->whereYear('date', $year)->orderBy('date')->get(), 'year' => $year]);
    }

    public function store(Request $request)
    {
        Holiday::query()->create($request->validate([
            'date' => ['required', 'date', 'unique:holidays,date'],
            'name' => ['required', 'string', 'max:100'],
        ]));

        return back()->with('success', 'Hari libur ditambahkan.');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return back()->with('success', 'Hari libur dihapus.');
    }
}
