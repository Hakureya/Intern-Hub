<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\AbsenceRequest;
use App\Models\NationalHoliday;
use Carbon\Carbon;

class SheetReportController extends Controller
{
    public function sheetReport()
    {
        $users = User::with(['division', 'mentor'])->orderByName()->inScope()->get();
        $selectedMonth = $this->normalizeMonth(request('month'));
        $nationalHolidays = $this->resolveNationalHolidaysForMonth($selectedMonth);

        $selectedUserId = (string) request('user_id', '');
        $selectedUser = null;
        if ($selectedUserId !== '') {
            $selectedUser = $users->firstWhere('id', (int) $selectedUserId);
        }

        $attendanceByDate = collect();
        $leaveByDate = collect();
        $pdfRows = collect();
        $monthlyRows = collect();
        $workingDays = 0;
        $attendanceDays = 0;
        if ($selectedUser) {
            [$year, $month] = explode('-', $selectedMonth);
            $startDate = sprintf('%04d-%02d-01', (int) $year, (int) $month);
            $endDate = date('Y-m-t', strtotime($startDate));

            $attendanceByDate = Attendance::where('user_id', $selectedUser->id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->get()
                ->keyBy('attendance_date');

            $leaveByDate = Leave::where('user_id', $selectedUser->id)
                ->whereBetween('leave_date', [$startDate, $endDate])
                ->get()
                ->keyBy('leave_date');

            $monthlyRows = $this->buildMonthlyAttendanceRows((int) $selectedUser->id, $selectedMonth, $nationalHolidays);
            $pdfRows = $monthlyRows
                ->map(function ($row, $index) {
                    $row['no'] = $index + 1;
                    $row['day_name'] = Carbon::parse($row['date'])->translatedFormat('l');
                    return $row;
                });

            $workingDays = $monthlyRows->filter(function ($row) {
                return !$row['is_holiday'];
            })->count();

            $attendanceDays = $monthlyRows->filter(function ($row) {
                return $row['status'] === 'Hadir';
            })->count();
        }

        return view('admin.sheet-report')->with([
            'users' => $users,
            'selectedMonth' => $selectedMonth,
            'selectedUserId' => $selectedUserId,
            'selectedUser' => $selectedUser,
            'attendanceByDate' => $attendanceByDate,
            'leaveByDate' => $leaveByDate,
            'monthlyRows' => $monthlyRows,
            'workingDays' => $workingDays,
            'attendanceDays' => $attendanceDays,
            'pdfRows' => $pdfRows,
            'nationalHolidays' => $nationalHolidays,
        ]);
    }

    private function normalizeMonth(?string $month): string
    {
        $month = $month ?: now()->format('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        return $month;
    }

    private function buildMonthlyAttendanceRows(int $userId, string $month, array $nationalHolidays = []): \Illuminate\Support\Collection
    {
        $start = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $attendanceByDate = Attendance::where('user_id', $userId)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy('attendance_date');

        $leaveByDate = Leave::where('user_id', $userId)
            ->whereBetween('leave_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy('leave_date');

        // Izin/sakit kini berupa rentang tanggal: setiap hari kerja dalam rentang ikut ditandai.
        $absenceByDate = AbsenceRequest::dateMapFor(
            $userId,
            $start,
            $end,
            ['approved', 'rejected'],
            $nationalHolidays
        );

        $rows = collect();
        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $date = $cursor->toDateString();
            $attendance = $attendanceByDate->get($date);
            $leave = $leaveByDate->get($date);
            $isWeekend = $cursor->isWeekend();
            $holidayCode = $nationalHolidays[$date] ?? null;
            $isNationalHoliday = !empty($holidayCode);
            $isHoliday = $isWeekend || $isNationalHoliday;

            $timeIn = $attendance && $attendance->attendance_time
                ? Carbon::parse($attendance->attendance_time)->format('H:i')
                : '-';
            $timeOut = $leave && $leave->leave_time
                ? Carbon::parse($leave->leave_time)->format('H:i')
                : '-';
            $rawTimeIn = $attendance && $attendance->attendance_time
                ? Carbon::parse($attendance->attendance_time)->format('H:i:s')
                : null;
            $rawTimeOut = $leave && $leave->leave_time
                ? Carbon::parse($leave->leave_time)->format('H:i:s')
                : null;
            $isLateIn = $rawTimeIn ? strtotime($rawTimeIn) > strtotime(Schedule::FIXED_TIME_IN) : false;
            $isEarlyOut = $rawTimeOut ? strtotime($rawTimeOut) < strtotime(Schedule::FIXED_TIME_OUT) : false;

            $status = 'Belum Presensi';
            $statusCode = '';
            $absenceRequest = $absenceByDate->get($date);
            $approvedAbsenceType = null;
            if ($absenceRequest && $absenceRequest->status === 'approved') {
                $approvedAbsenceType = strtolower((string) ($absenceRequest->absence_type ?: 'izin'));
            }

            if ($absenceRequest && $absenceRequest->status === 'approved' && $approvedAbsenceType !== 'lupa_absensi') {
                if ($approvedAbsenceType === 'sakit') {
                    $status = 'Sakit';
                    $statusCode = 'S';
                } else {
                    $status = 'Izin';
                    $statusCode = 'I';
                }
            } elseif ($absenceRequest && $absenceRequest->status === 'rejected') {
                $status = 'Tanpa Keterangan';
                $statusCode = 'TK';
            } elseif ($attendance || $leave) {
                if ($isLateIn || $isEarlyOut) {
                    $status = 'Terlambat';
                    $statusCode = 'T';
                } else {
                    $status = 'Hadir';
                    $statusCode = 'H';
                }
            } elseif ($isHoliday) {
                $status = 'Libur';
                $statusCode = $isNationalHoliday ? $holidayCode : 'LS';
            }

            $notes = [];
            if ($attendance && $attendance->note) {
                $notes[] = 'Catatan masuk: ' . $attendance->note;
            }
            if ($leave && $leave->note) {
                $notes[] = 'Catatan pulang: ' . $leave->note;
            }

            $description = 'Masuk: ' . $timeIn . ' | Pulang: ' . $timeOut;
            if (!empty($notes)) {
                $description .= ' | ' . implode(' | ', $notes);
            }

            $rows->push([
                'date' => $date,
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'status' => $status,
                'status_code' => $statusCode,
                'description' => $description,
                'is_holiday' => $isHoliday,
                'is_national_holiday' => $isNationalHoliday,
            ]);
        }

        return $rows;
    }

    private function resolveNationalHolidaysForMonth(string $month): array
    {
        return NationalHoliday::mapForMonth($month);
    }
}
