<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Leave;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AttendanceController extends Controller
{   
    //show attendance 
    public function index(Request $request)
    {
        $selectedMonth = $request->query('month', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = now()->format('Y-m');
        }

        [$year, $month] = explode('-', $selectedMonth);
        $startDate = sprintf('%04d-%02d-01', (int) $year, (int) $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $selectedUserId = $request->query('user_id');
        $users = User::orderByName()->inScope()->get();
        $scopeIds = $users->pluck('id');
        $isMentor = auth()->user()->isMentor();
        if ($selectedUserId && ! $scopeIds->contains((int) $selectedUserId)) {
            $selectedUserId = null;
        }

        $attendanceQuery = Attendance::with('user')
            ->whereBetween('attendance_date', [$startDate, $endDate]);
        $leaveQuery = Leave::with('user')
            ->whereBetween('leave_date', [$startDate, $endDate]);

        if ($isMentor) {
            $attendanceQuery->whereIn('user_id', $scopeIds);
            $leaveQuery->whereIn('user_id', $scopeIds);
        }

        if ($selectedUserId) {
            $attendanceQuery->where('user_id', $selectedUserId);
            $leaveQuery->where('user_id', $selectedUserId);
        }

        $attendanceLogs = $attendanceQuery->get()->map(function ($attendance) {
            $date = $attendance->attendance_date;
            $time = $attendance->attendance_time;
            $timeIn = Schedule::FIXED_TIME_IN;
            $attendanceTs = strtotime($date . ' ' . $time);
            $scheduleTs = strtotime($date . ' ' . $timeIn);
            $diffSeconds = abs($attendanceTs - $scheduleTs);
            $isLate = $attendanceTs > $scheduleTs;

            return [
                'type' => 'Masuk',
                'date' => $date,
                'time' => $time,
                'status' => $isLate ? 'Terlambat' : 'Tepat Waktu',
                'diff_seconds' => $diffSeconds,
                'duration' => null,
                'note' => $attendance->note,
                'user_id' => $attendance->user_id,
                'name' => $attendance->user ? $attendance->user->name : '-',
                'timestamp' => $attendanceTs,
            ];
        });

        $leaveLogs = $leaveQuery->get()->map(function ($leave) {
            $date = $leave->leave_date;
            $time = $leave->leave_time;
            $timeOut = Schedule::FIXED_TIME_OUT;
            $leaveTs = strtotime($date . ' ' . $time);
            $scheduleTs = strtotime($date . ' ' . $timeOut);
            $diffSeconds = abs($leaveTs - $scheduleTs);
            $isEarly = $leaveTs < $scheduleTs;

            return [
                'type' => 'Pulang',
                'date' => $date,
                'time' => $time,
                'status' => $isEarly ? 'Pulang Cepat' : 'Tepat Waktu',
                'diff_seconds' => $diffSeconds,
                'duration' => null,
                'note' => $leave->note,
                'user_id' => $leave->user_id,
                'name' => $leave->user ? $leave->user->name : '-',
                'timestamp' => $leaveTs,
            ];
        });

        $logs = $attendanceLogs
            ->merge($leaveLogs)
            ->sortByDesc('timestamp')
            ->values();

        $groupedLogs = $logs->groupBy(function ($log) {
            $date = $log['date'] ?? null;
            if (!$date) {
                return 'unknown';
            }
            $carbon = Carbon::parse($date);
            $weekOfMonth = (int) floor((((int) $carbon->format('j')) - 1) / 7) + 1;
            return $carbon->format('Y-m') . '-' . $weekOfMonth;
        });
        $groupedLogs = $groupedLogs->sortKeys()->map(function ($weekLogs) {
            return $weekLogs->sortBy('timestamp')->values();
        });
        $logs = $groupedLogs->flatten(1)->values();

        return view('admin.attendance')->with([
            'logs' => $logs,
            'groupedLogs' => $groupedLogs,
            'users' => $users,
            'selectedUserId' => $selectedUserId,
            'selectedMonth' => $selectedMonth,
        ]);
    }
}
