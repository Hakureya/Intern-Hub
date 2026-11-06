<?php

namespace App\Http\Controllers;

use App\Models\InternshipDraftDocument;
use App\Models\MonthlyReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class MonthlyReportController extends Controller
{
    public function userIndex()
    {
        $user = auth()->user();

        $reports = collect();

        if ($user) {
            $reports = MonthlyReport::where('user_id', $user->id)
                ->orderBy('report_month', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $latestTemplate = InternshipDraftDocument::where('document_type', 'monthly')
            ->orderBy('created_at', 'desc')
            ->first();
        $templateDocuments = InternshipDraftDocument::where('document_type', 'monthly')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('user.monthly-report')->with([
            'user' => $user,
            'reports' => $reports,
            'latestTemplate' => $latestTemplate,
            'templateDocuments' => $templateDocuments,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'monthly_report' => 'required|file|mimes:pdf|max:5120|mimetypes:application/pdf',
            'report_month' => 'required|date_format:Y-m',
        ]);

        $user = auth()->user();

        $file = $request->file('monthly_report');
        $timestamp = now()->format('YmdHis');
        $extension = $file->getClientOriginalExtension();
        $month = $request->report_month;
        $safeName = 'monthly-report-' . $user->id . '-' . $timestamp . '.' . $extension;
        $path = $file->storeAs('monthly-reports/' . $month, $safeName, 'public');

        $report = new MonthlyReport();
        $report->user_id = $user->id;
        $report->uploaded_by = $user->id;
        $report->report_month = $month;
        $report->file_name = $file->getClientOriginalName();
        $report->file_path = $path;
        $report->file_size = $file->getSize();
        $report->mime_type = $file->getClientMimeType();
        $report->save();

        flash()->success('Berhasil', 'Laporan bulanan berhasil diunggah.');
        return redirect()->route('user.monthly-report');
    }

    public function index()
    {
        $reports = MonthlyReport::with(['user', 'uploadedBy'])
            ->when(auth()->user()->isMentor(), fn ($q) => $q->whereIn('user_id', User::peserta()->inScope()->select('users.id')))
            ->orderBy('report_month', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.monthly-report')->with(['reports' => $reports]);
    }

    public function download(MonthlyReport $monthlyReport)
    {
        $user = auth()->user();
        $isAdmin = $user && ($user->hasRole('admin') || ($user->isMentor() && optional($monthlyReport->user)->isVisibleTo($user)));

        if (!$isAdmin) {
            if (!$user || $user->id !== $monthlyReport->user_id) {
                abort(403);
            }
        }

        if (!Storage::disk('public')->exists($monthlyReport->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->download($monthlyReport->file_path, $monthlyReport->file_name);
    }

    public function view(MonthlyReport $monthlyReport)
    {
        $user = auth()->user();
        $isAdmin = $user && ($user->hasRole('admin') || ($user->isMentor() && optional($monthlyReport->user)->isVisibleTo($user)));

        if (!$isAdmin) {
            if (!$user || $user->id !== $monthlyReport->user_id) {
                abort(403);
            }
        }

        if (!Storage::disk('public')->exists($monthlyReport->file_path)) {
            abort(404);
        }

        $fullPath = Storage::disk('public')->path($monthlyReport->file_path);
        $mimeType = $monthlyReport->mime_type ?: 'application/pdf';

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $monthlyReport->file_name . '"',
        ]);
    }

    public function destroy(MonthlyReport $monthlyReport)
    {
        // Hapus berkas fisik (jika ada), lalu hapus datanya.
        if ($monthlyReport->file_path && Storage::disk('public')->exists($monthlyReport->file_path)) {
            Storage::disk('public')->delete($monthlyReport->file_path);
        }

        $monthlyReport->delete();

        flash()->success('Berhasil', 'Laporan bulanan berhasil dihapus.');
        return redirect()->route('monthly-report.index');
    }

    public function exportMonthZip(string $month)
    {
        $reports = MonthlyReport::with(['user'])
            ->when(auth()->user()->isMentor(), fn ($q) => $q->whereIn('user_id', User::peserta()->inScope()->select('users.id')))
            ->where('report_month', $month)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($reports->isEmpty()) {
            flash()->error('Gagal', 'Tidak ada file pada bulan tersebut.');
            return redirect()->route('monthly-report.index');
        }

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $zipName = 'monthly-report-' . $month . '.zip';
        $zipPath = $tmpDir . DIRECTORY_SEPARATOR . $zipName;
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            flash()->error('Gagal', 'Gagal membuat file ZIP.');
            return redirect()->route('monthly-report.index');
        }

        foreach ($reports as $report) {
            if (!Storage::disk('public')->exists($report->file_path)) {
                continue;
            }
            $fullPath = Storage::disk('public')->path($report->file_path);
            $userId = optional($report->user)->id ?? 'unknown';
            $userName = optional($report->user)->name ?? 'unknown';
            $filename = $userId . '-' . preg_replace('/[^a-zA-Z0-9_\-]+/', '-', $userName) . '-' . $report->file_name;
            $zip->addFile($fullPath, $filename);
        }

        $zip->close();

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }
}
