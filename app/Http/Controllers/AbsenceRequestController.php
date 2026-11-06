<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\AbsenceRequest;
use App\Models\Attendance;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AbsenceRequestController extends Controller
{
    public function userIndex()
    {
        $user = auth()->user();
        $selectedType = $this->resolveAbsenceTypeFilter(request('type'));

        $requests = collect();
        if ($user) {
            $query = AbsenceRequest::where('user_id', $user->id);
            if ($selectedType === 'izin_sakit') {
                $query->whereIn('absence_type', ['izin', 'sakit']);
            } elseif ($selectedType === 'lupa_absensi') {
                $query->where('absence_type', 'lupa_absensi');
            }

            $requests = $query
                ->orderBy('absence_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('user.absence-request')->with([
            'user' => $user,
            'requests' => $requests,
            'selectedType' => $selectedType,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'absence_date' => 'required|date',
            // Izin/sakit: wajib ada tanggal selesai (boleh sama dengan tanggal mulai untuk 1 hari).
            'absence_end_date' => 'required_if:absence_type,izin,sakit|nullable|date|after_or_equal:absence_date',
            'absence_type' => 'required|in:izin,sakit,lupa_absensi',
            'reason' => 'required|string|max:2000',
            'document' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx',
            'correction_time_in' => 'nullable|date_format:H:i',
            'correction_time_out' => 'nullable|date_format:H:i',
        ], [
            'absence_end_date.required_if' => 'Tanggal selesai wajib diisi untuk izin atau sakit.',
            'absence_end_date.date' => 'Format tanggal selesai tidak valid.',
            'absence_end_date.after_or_equal' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
        ]);

        // Lupa absensi selalu 1 tanggal. izin/sakit pakai rentang tanggal.
        $isIzinSakit = in_array($request->absence_type, ['izin', 'sakit'], true);
        $startDate = Carbon::parse($request->absence_date)->toDateString();
        $endDate = $isIzinSakit
            ? Carbon::parse($request->absence_end_date)->toDateString()
            : $startDate;

        $totalDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        if ($totalDays > AbsenceRequest::MAX_RANGE_DAYS) {
            return back()
                ->withErrors(['absence_end_date' => 'Rentang izin/sakit maksimal ' . AbsenceRequest::MAX_RANGE_DAYS . ' hari per pengajuan.'])
                ->withInput();
        }

        if ($request->absence_type === 'lupa_absensi' && !$request->filled('correction_time_in') && !$request->filled('correction_time_out')) {
            return back()
                ->withErrors(['correction_time_in' => 'Isi minimal jam masuk atau jam pulang untuk pengajuan lupa absensi.'])
                ->withInput();
        }

        $user = auth()->user();

        // Cegah pengajuan izin/sakit yang bertumpuk dengan pengajuan lain yang masih aktif.
        if ($isIzinSakit) {
            $overlap = AbsenceRequest::where('user_id', $user->id)
                ->whereIn('absence_type', ['izin', 'sakit'])
                ->whereIn('status', ['pending', 'approved'])
                ->overlapping($startDate, $endDate)
                ->first();

            if ($overlap) {
                $statusLabel = $overlap->status === 'approved' ? 'disetujui' : 'menunggu persetujuan';
                return back()
                    ->withErrors(['absence_date' => 'Tanggal tersebut sudah memiliki pengajuan ' . $overlap->absence_type
                        . ' (' . $overlap->date_range_label . ', ' . $statusLabel . ').'])
                    ->withInput();
            }
        }

        $absenceRequest = new AbsenceRequest();
        $absenceRequest->user_id = $user->id;
        $absenceRequest->absence_date = $startDate;
        $absenceRequest->absence_end_date = $endDate;
        $absenceRequest->absence_type = $request->absence_type;
        $absenceRequest->correction_time_in = $this->normalizeTimeInput($request->input('correction_time_in'));
        $absenceRequest->correction_time_out = $this->normalizeTimeInput($request->input('correction_time_out'));
        $absenceRequest->reason = trim((string) $request->reason);
        $absenceRequest->status = 'pending';

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $safeName = 'absence-' . $user->id . '-' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('absence-requests/' . date('Y-m'), $safeName, 'public');
            $absenceRequest->document_name = $file->getClientOriginalName();
            $absenceRequest->document_path = $path;
            $absenceRequest->document_size = $file->getSize();
            $absenceRequest->document_mime = $file->getClientMimeType();
        }

        $absenceRequest->save();

        flash()->success('Berhasil', 'Pengajuan ketidakhadiran berhasil dikirim.');
        $menuType = $this->resolveAbsenceTypeFilter($request->input('menu_type'));
        return redirect()->route('user.absence.index', ['type' => $menuType]);
    }

    public function index(Request $request)
    {
        $selectedMonth = $request->query('month', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = now()->format('Y-m');
        }
        $selectedType = $this->resolveAbsenceTypeFilter($request->query('type'));

        $start = $selectedMonth . '-01';
        $end = date('Y-m-t', strtotime($start));

        // Rentang izin/sakit yang beririsan dengan bulan terpilih ikut tampil.
        $query = AbsenceRequest::with(['user', 'reviewer'])
            ->overlapping($start, $end);

        if ($selectedType === 'izin_sakit') {
            $query->whereIn('absence_type', ['izin', 'sakit']);
        } elseif ($selectedType === 'lupa_absensi') {
            $query->where('absence_type', 'lupa_absensi');
        }

        $requests = $query
            ->orderBy('absence_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.absence-requests')->with([
            'requests' => $requests,
            'selectedMonth' => $selectedMonth,
            'selectedType' => $selectedType,
        ]);
    }

    public function update(Request $request, AbsenceRequest $absenceRequest)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
            'admin_note' => 'nullable|string|max:2000',
            'correction_time_in' => 'nullable|date_format:H:i',
            'correction_time_out' => 'nullable|date_format:H:i',
        ]);

        $isLupaAbsensi = strtolower((string) $absenceRequest->absence_type) === 'lupa_absensi';
        if ($isLupaAbsensi && $request->status === 'approved') {
            $timeInInput = $request->input('correction_time_in');
            $timeOutInput = $request->input('correction_time_out');
            if (empty($timeInInput) && empty($timeOutInput) && empty($absenceRequest->correction_time_in) && empty($absenceRequest->correction_time_out)) {
                return back()->withErrors([
                    'correction_time_in_' . $absenceRequest->id => 'Jam koreksi wajib diisi untuk menyetujui lupa absensi.',
                ]);
            }
        }

        $newTimeIn = $this->normalizeTimeInput($request->input('correction_time_in'));
        $newTimeOut = $this->normalizeTimeInput($request->input('correction_time_out'));
        if ($newTimeIn !== null) {
            $absenceRequest->correction_time_in = $newTimeIn;
        }
        if ($newTimeOut !== null) {
            $absenceRequest->correction_time_out = $newTimeOut;
        }

        $absenceRequest->status = $request->status;
        $absenceRequest->admin_note = $request->admin_note;
        $absenceRequest->reviewed_by = optional(auth()->user())->id;
        $absenceRequest->reviewed_at = now();

        // Status pengajuan dan koreksi absensi disimpan dalam satu transaksi
        // jika koreksi gagal, status tidak ikut berubah menjadi "approved"
        DB::transaction(function () use ($absenceRequest, $isLupaAbsensi, $request): void {
            $absenceRequest->save();

            if ($isLupaAbsensi && $request->status === 'approved') {
                $this->applyManualAttendanceCorrection($absenceRequest);
            }
        });

        flash()->success('Berhasil', 'Status pengajuan ketidakhadiran berhasil diperbarui.');
        return back();
    }

    public function download(AbsenceRequest $absenceRequest)
    {
        $this->authorizeFileAccess($absenceRequest);

        if (!$absenceRequest->document_path || !Storage::disk('public')->exists($absenceRequest->document_path)) {
            abort(404);
        }

        $downloadName = $absenceRequest->document_name ?: basename($absenceRequest->document_path);
        return Storage::disk('public')->download($absenceRequest->document_path, $downloadName);
    }

    public function view(AbsenceRequest $absenceRequest)
    {
        $this->authorizeFileAccess($absenceRequest);

        if (!$absenceRequest->document_path || !Storage::disk('public')->exists($absenceRequest->document_path)) {
            abort(404);
        }

        $fullPath = Storage::disk('public')->path($absenceRequest->document_path);
        $mimeType = $absenceRequest->document_mime ?: 'application/octet-stream';
        $filename = $absenceRequest->document_name ?: basename($absenceRequest->document_path);

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function authorizeFileAccess(AbsenceRequest $absenceRequest): void
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole('admin');

        if ($isAdmin) {
            return;
        }

        if (!$user || (int) $user->id !== (int) $absenceRequest->user_id) {
            abort(403);
        }
    }

    private function normalizeTimeInput(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('H:i', $value)->format('H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function applyManualAttendanceCorrection(AbsenceRequest $absenceRequest): void
    {
        $date = Carbon::parse($absenceRequest->absence_date)->toDateString();
        $timeIn = $absenceRequest->correction_time_in ?: null;
        $timeOut = $absenceRequest->correction_time_out ?: null;

        DB::transaction(function () use ($absenceRequest, $date, $timeIn, $timeOut): void {
            // Jangan pakai firstOrNew([...]): model Attendance & Leave tidak punya $fillable,
            // sehingga baris baru memicu MassAssignmentException.
            $existingAttendance = Attendance::where('user_id', $absenceRequest->user_id)
                ->where('attendance_date', $date)
                ->first();
            $existingLeave = Leave::where('user_id', $absenceRequest->user_id)
                ->where('leave_date', $date)
                ->first();

            // Simpan kondisi SEBELUM koreksi (hanya sekali, supaya tidak tertimpa saat disetujui ulang).
            if (!$absenceRequest->correction_applied_at) {
                $absenceRequest->original_time_in = $existingAttendance ? $existingAttendance->attendance_time : null;
                $absenceRequest->original_time_out = $existingLeave ? $existingLeave->leave_time : null;
                $absenceRequest->correction_applied_at = now();
                $absenceRequest->save();
            }

            if ($timeIn) {
                $attendance = $existingAttendance ?? new Attendance();
                $attendance->user_id = $absenceRequest->user_id;
                $attendance->attendance_date = $date;
                $attendance->type = 0;
                $attendance->attendance_time = $timeIn;
                $attendance->status = strtotime($timeIn) <= strtotime(Schedule::FIXED_TIME_IN) ? 1 : 0;
                $attendance->note = 'Koreksi admin dari pengajuan lupa absensi';
                $attendance->save();
            }

            if ($timeOut) {
                $leave = $existingLeave ?? new Leave();
                $leave->user_id = $absenceRequest->user_id;
                $leave->leave_date = $date;
                $leave->type = 1;
                $leave->leave_time = $timeOut;
                $leave->status = strtotime($timeOut) >= strtotime(Schedule::FIXED_TIME_OUT) ? 1 : 0;
                $leave->note = 'Koreksi admin dari pengajuan lupa absensi';
                $leave->save();
            }
        });
    }

    private function resolveAbsenceTypeFilter(?string $type): string
    {
        $type = strtolower((string) $type);
        if (!in_array($type, ['izin_sakit', 'lupa_absensi', 'all'], true)) {
            return 'izin_sakit';
        }
        return $type;
    }
}
