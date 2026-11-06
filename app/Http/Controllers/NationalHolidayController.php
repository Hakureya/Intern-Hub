<?php

namespace App\Http\Controllers;

use App\Models\NationalHoliday;
use Illuminate\Http\Request;

class NationalHolidayController extends Controller
{
    public function index(Request $request)
    {
        $selectedMonth = $request->query('month', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = now()->format('Y-m');
        }

        $start = $selectedMonth . '-01';
        $end = date('Y-m-t', strtotime($start));

        $holidays = NationalHoliday::query()
            ->whereBetween('holiday_date', [$start, $end])
            ->orderBy('holiday_date')
            ->get();

        return view('admin.national-holidays')->with([
            'holidays' => $holidays,
            'selectedMonth' => $selectedMonth,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'holiday_date' => 'required|date|unique:national_holidays,holiday_date',
            'name' => 'required|string|max:255',
            'type' => 'required|in:LH,CB',
        ]);

        NationalHoliday::create([
            'holiday_date' => $request->holiday_date,
            'name' => trim((string) $request->name),
            'type' => $request->type,
        ]);

        flash()->success('Berhasil', 'Hari libur berhasil ditambahkan.');
        return back();
    }

    public function update(Request $request, NationalHoliday $nationalHoliday)
    {
        $request->validate([
            'holiday_date' => 'required|date|unique:national_holidays,holiday_date,' . $nationalHoliday->id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:LH,CB',
        ]);

        $nationalHoliday->holiday_date = $request->holiday_date;
        $nationalHoliday->name = trim((string) $request->name);
        $nationalHoliday->type = $request->type;
        $nationalHoliday->save();

        flash()->success('Berhasil', 'Hari libur berhasil diperbarui.');
        return back();
    }

    public function destroy(NationalHoliday $nationalHoliday)
    {
        $nationalHoliday->delete();
        flash()->success('Berhasil', 'Hari libur berhasil dihapus.');
        return back();
    }
}
