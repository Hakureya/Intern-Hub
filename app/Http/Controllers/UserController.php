<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\MonthlyReport;
use App\Models\FinalReport;
use App\Models\AbsenceRequest;
use App\Models\AttendanceDraftDocument;
use App\Exports\UsersDetailExport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Daftar dan detail peserta magang (hanya baca).
 * Membuat, mengubah, dan menghapus peserta dilakukan lewat akun di Manajemen Akun,
 * karena peserta adalah akun di tabel users.
 */
class UserController extends Controller
{
    public function index()
    {
        $users = User::peserta()->inScope()->with('division')->get();

        return view('admin.users')->with(['users' => $users]);
    }

    /** Alamat /users/{nama} dicari hanya di antara peserta (akun berperan "user"). */
    public function show(string $name)
    {
        $user = User::peserta()->inScope()->where((new User)->getRouteKeyName(), $name)->firstOrFail();

        $stats = [
            'attendance_count' => Attendance::where('user_id', $user->id)->count(),
            'leave_count' => Leave::where('user_id', $user->id)->count(),
            'absence_request_count' => AbsenceRequest::where('user_id', $user->id)->count(),
            'monthly_report_count' => MonthlyReport::where('user_id', $user->id)->count(),
            'final_report_count' => FinalReport::where('user_id', $user->id)->count(),
            'draft_document_count' => AttendanceDraftDocument::where('user_id', $user->id)->count(),
        ];

        return view('admin.user-show', compact('user', 'stats'));
    }

    public function exportDetail()
    {
        return Excel::download(
            new UsersDetailExport,
            'peserta-magang-detail-' . now()->format('Ymd-His') . '.xlsx'
        );
    }
}
