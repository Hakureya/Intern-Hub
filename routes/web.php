<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MagangRegistrationController;

Route::get('/', [MagangRegistrationController::class, 'landing'])->name('welcome');
Route::prefix('magang')->name('magang.')->group(function () {
    Route::get('/daftar', [MagangRegistrationController::class, 'form'])->name('form');
    Route::post('/daftar', [MagangRegistrationController::class, 'submit'])->name('submit');
    Route::get('/status', [MagangRegistrationController::class, 'status'])->name('status');
    Route::get('/cek-status', [MagangRegistrationController::class, 'cekStatusForm'])->name('cek-status');
    Route::post('/cek-status', [MagangRegistrationController::class, 'cekStatusResult'])->name('cek-status.result');
});

Auth::routes(['register' => true, 'reset' => false]);

Route::group(['middleware' => ['auth', 'Role'], 'roles' => ['admin']], function () {
    Route::get('/users/export-detail', '\App\Http\Controllers\UserController@exportDetail')->name('users.export.detail');
    Route::resource('/schedule', '\App\Http\Controllers\ScheduleController'); //admin jadwal (keep dulu)

    Route::delete('/final-report/{finalReport}', '\App\Http\Controllers\FinalReportController@destroy')->name('final-report.destroy'); //admin hapus laporan akhir

    Route::delete('/monthly-report/{monthlyReport}', '\App\Http\Controllers\MonthlyReportController@destroy')->name('monthly-report.destroy'); //admin hapus laporan bulanan

    Route::get('/absence-requests', '\App\Http\Controllers\AbsenceRequestController@index')->name('absence-requests.index'); //admin ketidakhadiran
    Route::patch('/absence-requests/{absenceRequest}', '\App\Http\Controllers\AbsenceRequestController@update')->name('absence-requests.update');
    Route::get('/absence-requests/{absenceRequest}/download', '\App\Http\Controllers\AbsenceRequestController@download')->name('absence-requests.download');
    Route::get('/absence-requests/{absenceRequest}/view', '\App\Http\Controllers\AbsenceRequestController@view')->name('absence-requests.view');

    Route::get('/divisions', '\App\Http\Controllers\DivisionController@index')->name('divisions.index'); //admin divisi
    Route::post('/divisions', '\App\Http\Controllers\DivisionController@store')->name('divisions.store');
    Route::patch('/divisions/{division}', '\App\Http\Controllers\DivisionController@update')->name('divisions.update');
    Route::delete('/divisions/{division}', '\App\Http\Controllers\DivisionController@destroy')->name('divisions.destroy');

    Route::get('/mentors', '\App\Http\Controllers\MentorController@index')->name('mentors.index'); //admin mentor
    Route::post('/mentors', '\App\Http\Controllers\MentorController@store')->name('mentors.store');
    Route::patch('/mentors/{mentor}', '\App\Http\Controllers\MentorController@update')->name('mentors.update');
    Route::delete('/mentors/{mentor}', '\App\Http\Controllers\MentorController@destroy')->name('mentors.destroy');

    Route::get('/national-holidays', '\App\Http\Controllers\NationalHolidayController@index')->name('national-holidays.index'); // admin cuti nasional
    Route::post('/national-holidays', '\App\Http\Controllers\NationalHolidayController@store')->name('national-holidays.store');
    Route::patch('/national-holidays/{nationalHoliday}', '\App\Http\Controllers\NationalHolidayController@update')->name('national-holidays.update');
    Route::delete('/national-holidays/{nationalHoliday}', '\App\Http\Controllers\NationalHolidayController@destroy')->name('national-holidays.destroy');

    Route::get('/attendance-drafts', '\App\Http\Controllers\AttendanceDraftController@adminIndex')->name('attendance-drafts.index'); //admin draft presensi
    Route::get('/attendance-drafts/{document}/view', '\App\Http\Controllers\AttendanceDraftController@view')->name('attendance-drafts.view');
    Route::get('/attendance-drafts/{document}/download', '\App\Http\Controllers\AttendanceDraftController@download')->name('attendance-drafts.download');

    Route::get('/internship-draft-documents', '\App\Http\Controllers\InternshipDraftDocumentController@index')->name('internship-draft-documents.index'); //admin draft dokumen magang
    Route::post('/internship-draft-documents', '\App\Http\Controllers\InternshipDraftDocumentController@store')->name('internship-draft-documents.store');
    Route::get('/internship-draft-documents/{document}/view', '\App\Http\Controllers\InternshipDraftDocumentController@view')->name('internship-draft-documents.view');
    Route::get('/internship-draft-documents/{document}/download', '\App\Http\Controllers\InternshipDraftDocumentController@download')->name('internship-draft-documents.download');
    Route::delete('/internship-draft-documents/{document}', '\App\Http\Controllers\InternshipDraftDocumentController@destroy')->name('internship-draft-documents.destroy');

    Route::get('/admin/magang', [MagangRegistrationController::class, 'adminDashboard'])->name('admin.magang.dashboard'); //admin pendaftaran magang
    Route::get('/admin/magang/export', [MagangRegistrationController::class, 'exportExcel'])->name('admin.magang.export');
    Route::get('/admin/magang/{id}', [MagangRegistrationController::class, 'adminDetail'])->name('admin.magang.detail');
    Route::post('/admin/magang/{id}/status', [MagangRegistrationController::class, 'updateStatus'])->name('admin.magang.update');


});
// admin + mentor (mentor hanya melihat data divisinya, difilter di controller)
Route::group(['middleware' => ['auth', 'Role'], 'roles' => ['admin', 'mentor']], function () {
    Route::get('/admin', '\App\Http\Controllers\AdminController@index')->name('admin'); //dashboard
    Route::get('/admin/profile', [\App\Http\Controllers\AdminProfileController::class, 'edit'])->name('admin.profile');
    Route::patch('/admin/profile', [\App\Http\Controllers\AdminProfileController::class, 'update'])->name('admin.profile.update');

    Route::resource('/users', '\App\Http\Controllers\UserController')->only(['index', 'show']); //peserta magang
    Route::get('/sheet-report', '\App\Http\Controllers\SheetReportController@sheetReport')->name('sheet-report'); //laporan presensi
    Route::get('/attendance', '\App\Http\Controllers\AttendanceController@index')->name('attendance'); //log pengelolaan

    Route::get('/monthly-report', '\App\Http\Controllers\MonthlyReportController@index')->name('monthly-report.index'); //laporan bulanan
    Route::get('/monthly-report/export/{month}', '\App\Http\Controllers\MonthlyReportController@exportMonthZip')->name('monthly-report.export');

    Route::get('/final-report', '\App\Http\Controllers\FinalReportController@index')->name('final-report.index'); //laporan akhir
    Route::get('/final-report/export/{month}', '\App\Http\Controllers\FinalReportController@exportMonthZip')->name('final-report.export');

    // log pekerjaan (mentor hanya divisinya)
    Route::get('/work-logs', [\App\Http\Controllers\WorkTaskController::class, 'index'])->name('work-logs.index');
    Route::get('/work-logs/create', [\App\Http\Controllers\WorkTaskController::class, 'create'])->name('work-logs.create');
    Route::post('/work-logs', [\App\Http\Controllers\WorkTaskController::class, 'store'])->name('work-logs.store');
    Route::get('/work-logs/{task}', [\App\Http\Controllers\WorkTaskController::class, 'show'])->where('task', '[0-9]+')->name('work-logs.show');
    Route::get('/work-logs/{task}/edit', [\App\Http\Controllers\WorkTaskController::class, 'edit'])->where('task', '[0-9]+')->name('work-logs.edit');
    Route::put('/work-logs/{task}', [\App\Http\Controllers\WorkTaskController::class, 'update'])->where('task', '[0-9]+')->name('work-logs.update');
    Route::post('/work-logs/{task}/assignees/{assignee}/status', [\App\Http\Controllers\WorkTaskController::class, 'updateStatus'])->where(['task' => '[0-9]+', 'assignee' => '[0-9]+'])->name('work-logs.status');
});

// hanya super admin
// Manajemen akun
Route::group(['middleware' => ['auth', 'Role'], 'roles' => ['super_admin']], function () {
    Route::get('/account-management', [\App\Http\Controllers\AccountManagementController::class, 'index'])->name('accounts.index'); //admin manajemen akun
    Route::post('/account-management', [\App\Http\Controllers\AccountManagementController::class, 'store'])->name('accounts.store');
    Route::patch('/account-management/{id}', [\App\Http\Controllers\AccountManagementController::class, 'update'])->name('accounts.update');
    Route::patch('/account-management/{id}/status', [\App\Http\Controllers\AccountManagementController::class, 'toggleStatus'])->name('accounts.status');
    Route::post('/account-management/{id}/reset-password', [\App\Http\Controllers\AccountManagementController::class, 'resetPassword'])->name('accounts.reset-password');
    Route::delete('/account-management/{id}', [\App\Http\Controllers\AccountManagementController::class, 'destroy'])->name('accounts.destroy');

    // Manajemen slide landing page
    Route::get('/slide-management', [\App\Http\Controllers\SlideManagementController::class, 'index'])->name('slides.index'); //admin manajemen slide
    Route::get('/slide-management/create', [\App\Http\Controllers\SlideManagementController::class, 'create'])->name('slides.create');
    Route::post('/slide-management', [\App\Http\Controllers\SlideManagementController::class, 'store'])->name('slides.store');
    Route::post('/slide-management/settings', [\App\Http\Controllers\SlideManagementController::class, 'settings'])->name('slides.settings');
    Route::get('/slide-management/{id}/edit', [\App\Http\Controllers\SlideManagementController::class, 'edit'])->name('slides.edit');
    Route::patch('/slide-management/{id}', [\App\Http\Controllers\SlideManagementController::class, 'update'])->name('slides.update');
    Route::patch('/slide-management/{id}/toggle', [\App\Http\Controllers\SlideManagementController::class, 'toggle'])->name('slides.toggle');
    Route::patch('/slide-management/{id}/move/{direction}', [\App\Http\Controllers\SlideManagementController::class, 'move'])->where('direction', 'up|down')->name('slides.move');
    Route::delete('/slide-management/{id}', [\App\Http\Controllers\SlideManagementController::class, 'destroy'])->name('slides.destroy');
});

Route::group(['middleware' => ['auth']], function () {
    Route::get('/home', '\App\Http\Controllers\HomeController@userDashboard')->name('home'); //user home

    Route::get('/user/attendance', '\App\Http\Controllers\UserAttendanceController@index')->name('user.attendance.index'); //user kehadiran
    Route::post('/user/attendance', '\App\Http\Controllers\UserAttendanceController@store')->name('user.attendance.store');
    Route::post('/home/time-in', '\App\Http\Controllers\UserAttendanceController@store')->name('home.timein.store');
    Route::post('/home/time-out', '\App\Http\Controllers\UserAttendanceController@storeLeave')->name('home.timeout.store');

    Route::post('/final-report', '\App\Http\Controllers\FinalReportController@store')->name('final-report.store');
    Route::get('/final-report/{finalReport}/download', '\App\Http\Controllers\FinalReportController@download')->name('final-report.download');
    Route::get('/final-report/{finalReport}/view', '\App\Http\Controllers\FinalReportController@view')->name('final-report.view');
    Route::get('/user/final-report', '\App\Http\Controllers\FinalReportController@userIndex')->name('user.final-report'); //user laporan akhir

    Route::post('/monthly-report', '\App\Http\Controllers\MonthlyReportController@store')->name('monthly-report.store');
    Route::get('/monthly-report/{monthlyReport}/download', '\App\Http\Controllers\MonthlyReportController@download')->name('monthly-report.download');
    Route::get('/monthly-report/{monthlyReport}/view', '\App\Http\Controllers\MonthlyReportController@view')->name('monthly-report.view');
    Route::get('/user/monthly-report', '\App\Http\Controllers\MonthlyReportController@userIndex')->name('user.monthly-report'); //user laporan bulanan

    Route::get('/user/profile', '\App\Http\Controllers\ProfileController@edit')->name('user.profile'); //user profil
    Route::post('/user/profile', '\App\Http\Controllers\ProfileController@update')->name('user.profile.update');

    Route::get('/user/ketidakhadiran', '\App\Http\Controllers\AbsenceRequestController@userIndex')->name('user.absence.index'); //user izin/sakit dan lupa absensi
    Route::post('/user/ketidakhadiran', '\App\Http\Controllers\AbsenceRequestController@store')->name('user.absence.store');
    Route::get('/user/ketidakhadiran/{absenceRequest}/download', '\App\Http\Controllers\AbsenceRequestController@download')->name('user.absence.download');
    Route::get('/user/ketidakhadiran/{absenceRequest}/view', '\App\Http\Controllers\AbsenceRequestController@view')->name('user.absence.view');

    Route::get('/user/attendance-history', '\App\Http\Controllers\UserAttendanceController@history')->name('user.attendance.history'); //user kalender kehadiran

    Route::get('/user/attendance-history/draft', '\App\Http\Controllers\AttendanceDraftController@userIndex')->name('user.attendance.draft'); //user upload draft presensi
    Route::post('/user/attendance-history/draft', '\App\Http\Controllers\AttendanceDraftController@store')->name('user.attendance.draft.store');
    Route::get('/user/attendance-history/draft/{document}/view', '\App\Http\Controllers\AttendanceDraftController@view')->name('user.attendance.draft.view');
    Route::get('/user/attendance-history/draft/{document}/download', '\App\Http\Controllers\AttendanceDraftController@download')->name('user.attendance.draft.download');

      Route::get('/user/template-dokumen/{document}/view', '\App\Http\Controllers\InternshipDraftDocumentController@view')->name('user.template.view');
  Route::get('/user/template-dokumen/{document}/download', '\App\Http\Controllers\InternshipDraftDocumentController@download')->name('user.template.download');

    // log pekerjaan peserta
    Route::get('/user/work-logs', [\App\Http\Controllers\UserWorkTaskController::class, 'index'])->name('user.work-logs.index');
    Route::get('/user/work-logs/create', [\App\Http\Controllers\UserWorkTaskController::class, 'create'])->name('user.work-logs.create');
    Route::post('/user/work-logs', [\App\Http\Controllers\UserWorkTaskController::class, 'store'])->name('user.work-logs.store');
    Route::post('/user/work-logs/requests/{partnerRequest}/respond', [\App\Http\Controllers\UserWorkTaskController::class, 'respond'])->where('partnerRequest', '[0-9]+')->name('user.work-logs.requests.respond');
    Route::get('/user/work-logs/{task}', [\App\Http\Controllers\UserWorkTaskController::class, 'show'])->where('task', '[0-9]+')->name('user.work-logs.show');
    Route::get('/user/work-logs/{task}/edit', [\App\Http\Controllers\UserWorkTaskController::class, 'edit'])->where('task', '[0-9]+')->name('user.work-logs.edit');
    Route::put('/user/work-logs/{task}', [\App\Http\Controllers\UserWorkTaskController::class, 'update'])->where('task', '[0-9]+')->name('user.work-logs.update');
    Route::post('/user/work-logs/{task}/attachments', [\App\Http\Controllers\UserWorkTaskController::class, 'storeAttachments'])->where('task', '[0-9]+')->name('user.work-logs.attachments.store');
    Route::delete('/user/work-logs/{task}/attachments/{attachment}', [\App\Http\Controllers\UserWorkTaskController::class, 'destroyAttachment'])->where(['task' => '[0-9]+', 'attachment' => '[0-9]+'])->name('user.work-logs.attachments.destroy');
    Route::post('/user/work-logs/{task}/withdraw', [\App\Http\Controllers\UserWorkTaskController::class, 'withdraw'])->where('task', '[0-9]+')->name('user.work-logs.withdraw');
    Route::post('/user/work-logs/{task}/submit', [\App\Http\Controllers\UserWorkTaskController::class, 'submit'])->where('task', '[0-9]+')->name('user.work-logs.submit');

    // file lampiran & bukti (akses dicek di controller)
    Route::get('/work-logs/{task}/reference-file', [\App\Http\Controllers\WorkTaskFileController::class, 'reference'])->where('task', '[0-9]+')->name('work-logs.file.reference');
    Route::get('/work-logs/attachment/{attachment}', [\App\Http\Controllers\WorkTaskFileController::class, 'attachment'])->where('attachment', '[0-9]+')->name('work-logs.file.attachment');

});

Route::get('lang/{locale}', function ($locale) {
    $allowed = ['id'];
    if (!in_array($locale, $allowed, true)) {
        abort(404);
    }
    session(['locale' => $locale]);
    return back();
})->name('lang.switch');

// Route::get('/attendance/assign', function () {
//     return view('attendance_leave_login');
// })->name('attendance.login');

// Route::post('/attendance/assign', '\App\Http\Controllers\AttendanceController@assign')->name('attendance.assign');


// Route::get('/leave/assign', function () {
//     return view('attendance_leave_login');
// })->name('leave.login');

// Route::post('/leave/assign', '\App\Http\Controllers\LeaveController@assign')->name('leave.assign');


// Route::get('{any}', 'App\http\controllers\VeltrixController@index');
