<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AbsenceRequest;
use App\Models\Attendance;
use App\Models\NationalHoliday;
use Carbon\Carbon;


class AdminController extends Controller
{

 
    public function index()
    {
        //Dashboard statistics 
        $today = Carbon::today();
        $ids = User::peserta()->inScope()->pluck('users.id')->all(); // mentor: hanya divisinya
        $totalUsers = count($ids);
        $AllAttendance = count(Attendance::whereIn('user_id', $ids)->whereAttendance_date($today->toDateString())->get());
        $ontimeUsers = count(Attendance::whereIn('user_id', $ids)->whereAttendance_date($today->toDateString())->whereStatus('1')->get());
        $lateUsers = count(Attendance::whereIn('user_id', $ids)->whereAttendance_date($today->toDateString())->whereStatus('0')->get());
            
        if($AllAttendance > 0){
                $percentageOntime = str_split(($ontimeUsers/ $AllAttendance)*100, 4)[0];
            }else {
                $percentageOntime = 0 ;
            }

        $monthStart = $today->copy()->startOfMonth()->toDateString();
        $monthEnd = $today->copy()->endOfMonth()->toDateString();
        $lastMonthStart = $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $lastMonthEnd = $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $thisMonthTotal = Attendance::whereIn('user_id', $ids)->whereBetween('attendance_date', [$monthStart, $monthEnd])->count();
        $lastMonthTotal = Attendance::whereIn('user_id', $ids)->whereBetween('attendance_date', [$lastMonthStart, $lastMonthEnd])->count();
        $thisMonthOnTime = Attendance::whereIn('user_id', $ids)->whereBetween('attendance_date', [$monthStart, $monthEnd])->whereStatus(1)->count();
        $thisMonthLate = Attendance::whereIn('user_id', $ids)->whereBetween('attendance_date', [$monthStart, $monthEnd])->whereStatus(0)->count();

        // Hari sakit/izin/tanpa keterangan dihitung dari pengajuan Ketidakhadiran (sama dengan Sheet Report).
        $absenceDays = AbsenceRequest::dayCountsFor(
            $today->copy()->startOfMonth(),
            $today->copy()->endOfMonth(),
            NationalHoliday::mapForMonth($today->format('Y-m')),
            $ids
        );
        $thisMonthSick = $absenceDays['sakit'];
        $thisMonthLeave = $absenceDays['izin'];
        $thisMonthNoNote = $absenceDays['tanpa_keterangan'];

        $daysInMonth = $today->daysInMonth;
        $dailyCounts = Attendance::whereIn('user_id', $ids)->selectRaw('attendance_date, COUNT(*) as total')
            ->whereBetween('attendance_date', [$monthStart, $monthEnd])
            ->groupBy('attendance_date')
            ->pluck('total', 'attendance_date')
            ->toArray();

        $chartLabels = [];
        $chartSeries = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::createFromDate($today->year, $today->month, $day)->format('Y-m-d');
            $chartLabels[] = $day;
            $chartSeries[] = isset($dailyCounts[$date]) ? (int) $dailyCounts[$date] : 0;
        }

        $data = [$totalUsers, $ontimeUsers, $lateUsers, $percentageOntime];
        
        return view('admin.index')->with([
            'data' => $data,
            'chartLabels' => $chartLabels,
            'chartSeries' => $chartSeries,
            'thisMonthTotal' => $thisMonthTotal,
            'lastMonthTotal' => $lastMonthTotal,
            'thisMonthOnTime' => $thisMonthOnTime,
            'thisMonthLate' => $thisMonthLate,
            'thisMonthSick' => $thisMonthSick,
            'thisMonthLeave' => $thisMonthLeave,
            'thisMonthNoNote' => $thisMonthNoNote,
        ]);
    }

}
