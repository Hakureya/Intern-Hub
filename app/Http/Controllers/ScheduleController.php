<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Http\Requests\ScheduleRequest;

class ScheduleController extends Controller
{
   
    public function index()
    {
        Schedule::query()->update([
            'time_in' => Schedule::FIXED_TIME_IN,
            'time_out' => Schedule::FIXED_TIME_OUT,
        ]);

        return view('admin.schedule')->with('schedules', Schedule::all());
        flash()->success('Berhasil','Jadwal berhasil dibuat.');

    }


    public function store(ScheduleRequest $request)
    {
        $request->merge([
            'time_in' => Schedule::timeInShort(),
            'time_out' => Schedule::timeOutShort(),
        ]);

        $request->validated();

        $schedule = new schedule;
        $schedule->slug = $request->slug;
        $schedule->time_in = Schedule::FIXED_TIME_IN;
        $schedule->time_out = Schedule::FIXED_TIME_OUT;
        $schedule->save();




        flash()->success('Berhasil','Jadwal berhasil dibuat.');
        return redirect()->route('schedule.index');

    }

    public function update(ScheduleRequest $request, Schedule $schedule)
    {
        $request->merge([
            'time_in' => Schedule::timeInShort(),
            'time_out' => Schedule::timeOutShort(),
        ]);

        $request->validated();

        $schedule->slug = $request->slug;
        $schedule->time_in = Schedule::FIXED_TIME_IN;
        $schedule->time_out = Schedule::FIXED_TIME_OUT;
        $schedule->save();
        flash()->success('Berhasil','Jadwal berhasil diperbarui.');
        return redirect()->route('schedule.index');


    }

  
    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        flash()->success('Berhasil','Jadwal berhasil dihapus.');
        return redirect()->route('schedule.index');
    }
}
