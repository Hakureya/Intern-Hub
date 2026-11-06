<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\AbsenceRequest;
use App\Models\Leave;
use App\Models\NationalHoliday;
use Carbon\Carbon;
use Illuminate\Http\Request;
use app\models\user;

class UserAttendanceController extends Controller
{
    public function index()
    {
        $data = $this->buildAttendanceContext();

        return view('user.attendance', $data);
    }

    public function history(Request $request)
    {
        $selectedMonth = $this->normalizeMonth($request->query('month'));
        $nationalHolidays = $this->resolveNationalHolidaysForMonth($selectedMonth);

        $data = $this->buildAttendanceContext();
        $rows = collect();
        $pdfRows = collect();
        if ($data['user']) {
            $rows = $this->buildMonthlyAttendanceRows((int) $data['user']->id, $selectedMonth, $nationalHolidays);
            $pdfRows = $rows->map(function ($row, $index) {
                $row['no'] = $index + 1;
                $row['day_name'] = Carbon::parse($row['date'])->translatedFormat('l');
                return $row;
            });
        }

        $dayDetails = [];
        if ($data['user']) {
            $dayDetails = $this->buildDayDetails((int) $data['user']->id, $selectedMonth, $rows);
        }

        // Saat halaman dibuka, detail otomatis menampilkan hari ini (jika berada di bulan yang ditampilkan).
        $today = now()->toDateString();
        $initialDate = isset($dayDetails[$today]) ? $today : null;

        return view('user.attendance-history', [
            'user' => $data['user'],
            'rows' => $rows,
            'pdfRows' => $pdfRows,
            'selectedMonth' => $selectedMonth,
            'nationalHolidays' => $nationalHolidays,
            'dayDetails' => $dayDetails,
            'initialDate' => $initialDate,
        ]);
    }

    private function buildAttendanceContext(): array
    {
        $user = auth()->user();

        $today = now()->toDateString();
        $hasAttendance = false;
        $lastAttendance = null;
        $hasLeave = false;
        $lastLeave = null;
        $logs = collect();

        if ($user) {
            $hasAttendance = Attendance::where('user_id', $user->id)
                ->whereDate('attendance_date', $today)
                ->exists();
            $lastAttendance = Attendance::where('user_id', $user->id)
                ->orderBy('attendance_date', 'desc')
                ->orderBy('attendance_time', 'desc')
                ->first();

            $hasLeave = \App\Models\Leave::where('user_id', $user->id)
                ->whereDate('leave_date', $today)
                ->exists();
            $lastLeave = \App\Models\Leave::where('user_id', $user->id)
                ->orderBy('leave_date', 'desc')
                ->orderBy('leave_time', 'desc')
                ->first();

            $attendanceLogs = Attendance::where('user_id', $user->id)
                ->orderBy('attendance_date', 'desc')
                ->orderBy('attendance_time', 'desc')
                ->get()
                ->map(function ($attendance) {
                    $datetime = $attendance->attendance_date . ' ' . $attendance->attendance_time;
                    return [
                        'datetime' => $datetime,
                        'timestamp' => strtotime($datetime),
                        'type' => 'Masuk',
                        'note' => $attendance->note,
                    ];
                })
                ->toBase();

            $leaveLogs = \App\Models\Leave::where('user_id', $user->id)
                ->orderBy('leave_date', 'desc')
                ->orderBy('leave_time', 'desc')
                ->get()
                ->map(function ($leave) {
                    $datetime = $leave->leave_date . ' ' . $leave->leave_time;
                    return [
                        'datetime' => $datetime,
                        'timestamp' => strtotime($datetime),
                        'type' => 'Pulang',
                        'note' => $leave->note,
                    ];
                })
                ->toBase();

            $logs = $attendanceLogs
                ->merge($leaveLogs)
                ->sortByDesc('timestamp')
                ->values()
                ->take(50);
        }

        $groupedLogs = $logs->groupBy(function ($log) {
            $date = $log['datetime'] ?? null;
            if (!$date) {
                return 'unknown';
            }
            $carbon = Carbon::parse($date);
            return $carbon->isoWeekYear . '-' . str_pad($carbon->isoWeek, 2, '0', STR_PAD_LEFT);
        });

        return [
            'user' => $user,
            'hasAttendance' => $hasAttendance,
            'lastAttendance' => $lastAttendance,
            'hasLeave' => $hasLeave,
            'lastLeave' => $lastLeave,
            'logs' => $logs,
            'groupedLogs' => $groupedLogs,
        ];
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

            $lateMinutes = null;
            if ($isLateIn) {
                $lateMinutes = (int) floor((strtotime($rawTimeIn) - strtotime(Schedule::FIXED_TIME_IN)) / 60);
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
                'note_in' => $attendance && $attendance->note ? $attendance->note : null,
                'note_out' => $leave && $leave->note ? $leave->note : null,
                'is_late_in' => $isLateIn,
                'is_early_out' => $isEarlyOut,
                'late_minutes' => $lateMinutes,
            ]);
        }

        return $rows;
    }

    /**
     * Susun detail per tanggal untuk panel "Detail tanggal" pada kalender.
     * Semua teks sudah diformat di sini; JavaScript hanya menampilkannya.
     */
    private function buildDayDetails(int $userId, string $month, \Illuminate\Support\Collection $rows): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // Semua status (menunggu, disetujui, ditolak) ditampilkan di detail.
        $requests = AbsenceRequest::where('user_id', $userId)
            ->overlapping($start->toDateString(), $end->toDateString())
            ->orderBy('created_at', 'desc')
            ->get();

        $statusLabels = [
            'Hadir' => 'Hadir',
            'Terlambat' => 'Hadir terlambat',
            'Sakit' => 'Sakit',
            'Izin' => 'Izin',
            'Cuti' => 'Cuti',
            'Perjalanan Dinas' => 'Perjalanan dinas',
            'Tanpa Keterangan' => 'Lupa presensi',
            'Libur' => 'Libur',
            'Belum Presensi' => 'Belum ada data',
        ];

        $details = [];
        foreach ($rows as $row) {
            $date = $row['date'];
            $carbon = Carbon::parse($date)->locale('id');

            $dayRequests = [];
            foreach ($requests as $absenceRequest) {
                if ($date < $absenceRequest->absence_date->toDateString() || $date > $absenceRequest->end_date->toDateString()) {
                    continue;
                }
                $dayRequests[] = $this->formatRequestForDetail($absenceRequest, $row);
            }

            // Penjelasan keterlambatan / pulang lebih awal.
            $lateInfo = [];
            if (!empty($row['is_late_in'])) {
                $lateInfo[] = ($row['late_minutes'] ?? 0) >= 1
                    ? 'Masuk melewati batas ' . Schedule::timeInShort() . ' (terlambat ' . $row['late_minutes'] . ' menit).'
                    : 'Masuk melewati batas ' . Schedule::timeInShort() . '.';
            }
            if (!empty($row['is_early_out'])) {
                $lateInfo[] = 'Pulang lebih awal dari batas ' . Schedule::timeOutShort() . '.';
            }

            // Catatan otomatis dari koreksi admin tidak perlu diulang di sini (sudah ada di bagian koreksi).
            $noteIn = $row['note_in'] && strpos($row['note_in'], 'Koreksi admin') !== 0 ? $row['note_in'] : null;
            $noteOut = $row['note_out'] && strpos($row['note_out'], 'Koreksi admin') !== 0 ? $row['note_out'] : null;

            $holidayLabel = null;
            if (!empty($row['is_national_holiday'])) {
                $holidayLabel = 'Libur nasional';
            } elseif (!empty($row['is_holiday'])) {
                $holidayLabel = 'Akhir pekan';
            }

            $details[$date] = [
                'date' => $date,
                'label' => $carbon->translatedFormat('j F Y'),
                'weekday' => $carbon->translatedFormat('l'),
                'status' => $row['status'],
                'status_label' => $statusLabels[$row['status']] ?? $row['status'],
                'time_in' => $row['time_in'] !== '-' ? $row['time_in'] : null,
                'time_out' => $row['time_out'] !== '-' ? $row['time_out'] : null,
                'late_info' => $lateInfo,
                'note_in' => $noteIn,
                'note_out' => $noteOut,
                'holiday_label' => $holidayLabel,
                'requests' => $dayRequests,
            ];
        }

        return $details;
    }

    private function formatRequestForDetail(AbsenceRequest $absenceRequest, array $row): array
    {
        $type = strtolower((string) ($absenceRequest->absence_type ?: 'izin'));
        $typeLabels = ['izin' => 'Izin', 'sakit' => 'Sakit', 'lupa_absensi' => 'Koreksi kehadiran'];
        $statusLabels = ['pending' => 'Menunggu persetujuan', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];
        $hm = function ($value) {
            return $value ? Carbon::parse($value)->format('H:i') : null;
        };

        $beforeIn = $hm($absenceRequest->original_time_in);
        $beforeOut = $hm($absenceRequest->original_time_out);
        $afterIn = $hm($absenceRequest->correction_time_in);
        $afterOut = $hm($absenceRequest->correction_time_out);

        if ($type === 'lupa_absensi') {
            if ($absenceRequest->status !== 'approved') {
                // Belum diterapkan: "sebelum" = jam yang tercatat sekarang.
                $beforeIn = $row['time_in'] !== '-' ? $row['time_in'] : null;
                $beforeOut = $row['time_out'] !== '-' ? $row['time_out'] : null;
            }
            // Jam yang tidak dikoreksi tetap sama seperti sebelumnya.
            $afterIn = $afterIn ?: $beforeIn;
            $afterOut = $afterOut ?: $beforeOut;
        }

        return [
            'id' => $absenceRequest->id,
            'type' => $type,
            'type_label' => $typeLabels[$type] ?? ucfirst($type),
            'status' => $absenceRequest->status,
            'status_label' => $statusLabels[$absenceRequest->status] ?? ucfirst((string) $absenceRequest->status),
            'submitted_at' => Carbon::parse($absenceRequest->created_at)->locale('id')->translatedFormat('j F Y, H:i'),
            'reason' => $absenceRequest->reason,
            'admin_note' => $absenceRequest->admin_note,
            'period' => $absenceRequest->date_range_label,
            'is_range' => $absenceRequest->is_range,
            'days' => $absenceRequest->days_count,
            'before_in' => $beforeIn,
            'before_out' => $beforeOut,
            'after_in' => $afterIn,
            'after_out' => $afterOut,
        ];
    }

    private function normalizeMonth(?string $month): string
    {
        $month = $month ?: now()->format('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        return $month;
    }

    private function resolveNationalHolidaysForMonth(string $month): array
    {
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));

        $mapped = NationalHoliday::query()
            ->whereBetween('holiday_date', [$start, $end])
            ->get()
            ->mapWithKeys(function ($row) {
                return [Carbon::parse($row->holiday_date)->toDateString() => strtoupper((string) $row->type)];
            })
            ->toArray();

        $raw = (string) env('NATIONAL_HOLIDAYS', '');
        if ($raw !== '') {
            $list = array_filter(array_map('trim', explode(',', $raw)));
            foreach ($list as $date) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && strpos($date, $month . '-') === 0) {
                    if (!isset($mapped[$date])) {
                        $mapped[$date] = 'LH';
                    }
                }
            }
        }

        return $mapped;
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $today = now()->toDateString();
        $alreadyRecorded = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->exists();

        if ($alreadyRecorded) {
            flash()->error('Gagal', 'Kehadiran hari ini sudah tercatat.');
            return redirect()->route('user.attendance.index');
        }

        $attendance = new Attendance();
        $attendance->user_id = $user->id;
        $attendance->attendance_time = now()->toTimeString();
        $attendance->attendance_date = $today;
        $timeIn = Schedule::FIXED_TIME_IN;
        $attendanceTime = date('H:i:s', strtotime($attendance->attendance_time));
        $attendance->status = $attendanceTime <= $timeIn ? 1 : 0;
        if ($attendance->status === 0 && $request->filled('note')) {
            $attendance->note = $request->note;
        }
        $attendance->type = 0;
        $attendance->save();

        flash()->success('Berhasil', 'Kehadiran berhasil dicatat.');
        return redirect()->route('user.attendance.index');
    }

    public function storeLeave(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            flash()->error('Gagal', 'Data karyawan tidak ditemukan.');
            return redirect()->route('user.attendance.index');
        }

        $today = now()->toDateString();
        $alreadyLeave = \App\Models\Leave::where('user_id', $user->id)
            ->whereDate('leave_date', $today)
            ->exists();

        if ($alreadyLeave) {
            flash()->error('Gagal', 'Jam pulang hari ini sudah tercatat.');
            return redirect()->route('user.attendance.index');
        }

        $hasClockedIn = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->exists();

        if (!$hasClockedIn) {
            flash()->error('Gagal', 'Anda belum melakukan absen masuk hari ini.');
            return redirect()->route('user.attendance.index');
        }

        $leave = new \App\Models\Leave();
        $leave->user_id = $user->id;
        $leave->leave_time = now()->toTimeString();
        $leave->leave_date = $today;
        $timeOut = Schedule::FIXED_TIME_OUT;
        $leaveTime = date('H:i:s', strtotime($leave->leave_time));
        $leave->status = $leaveTime >= $timeOut ? 1 : 0;
        if ($leave->status === 0 && $request->filled('note')) {
            $leave->note = $request->note;
        }
        $leave->type = 1;
        $leave->save();

        flash()->success('Berhasil', 'Jam pulang berhasil dicatat.');
        return redirect()->route('user.attendance.index');
    }
}
