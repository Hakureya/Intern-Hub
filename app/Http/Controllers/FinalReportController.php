<?php

namespace App\Http\Controllers;

use App\Models\FinalReport;
use App\Models\InternshipDraftDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class FinalReportController extends Controller
{
    public function userIndex()
    {
        $user = auth()->user();

        $reports = collect();
        $lastFinalReport = null;

        if ($user) {
            $reports = FinalReport::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
            $lastFinalReport = $reports->first();
        }

        $latestTemplate = InternshipDraftDocument::where('document_type', 'final')
            ->orderBy('created_at', 'desc')
            ->first();
        $templateDocuments = InternshipDraftDocument::where('document_type', 'final')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('user.final-report')->with([
            'user' => $user,
            'reports' => $reports,
            'lastFinalReport' => $lastFinalReport,
            'latestTemplate' => $latestTemplate,
            'templateDocuments' => $templateDocuments,
        ]);
    }

    public function index()
    {
        $reports = FinalReport::with(['user', 'uploadedBy'])
            ->when(auth()->user()->isMentor(), fn ($q) => $q->whereIn('user_id', User::peserta()->inScope()->select('users.id')))
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.final-report')->with(['reports' => $reports]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'final_report' => 'required|file|mimes:pdf|max:10240|mimetypes:application/pdf',
            'what_learned' => 'nullable|string|max:5000',
            'challenges' => 'nullable|string|max:5000',
            'breakthrough_solution' => 'nullable|string|max:5000',
            'suggestions_for_company' => 'nullable|string|max:5000',
        ]);

        $user = auth()->user();

        $file = $request->file('final_report');
        $timestamp = now()->format('YmdHis');
        $extension = $file->getClientOriginalExtension();
        $safeName = 'final-report-' . $user->id . '-' . $timestamp . '.' . $extension;
        $month = now()->format('Y-m');
        $path = $file->storeAs('final-reports/' . $month, $safeName, 'public');

        $report = new FinalReport();
        $report->user_id = $user->id;
        $report->uploaded_by = $user->id;
        $report->file_name = $file->getClientOriginalName();
        $report->file_path = $path;
        $report->file_size = $file->getSize();
        $report->mime_type = $file->getClientMimeType();
        $report->what_learned = $request->input('what_learned');
        $report->challenges = $request->input('challenges');
        $report->breakthrough_solution = $request->input('breakthrough_solution');
        $report->suggestions_for_company = $request->input('suggestions_for_company');
        $report->save();

        flash()->success('Berhasil', 'Laporan akhir berhasil diunggah.');
        return redirect()->route('user.final-report');
    }

    public function download(FinalReport $finalReport)
    {
        $user = auth()->user();
        $isAdmin = $user && ($user->hasRole('admin') || ($user->isMentor() && optional($finalReport->user)->isVisibleTo($user)));

        if (!$isAdmin) {
            if (!$user || $user->id !== $finalReport->user_id) {
                abort(403);
            }
        }

        if (!Storage::disk('public')->exists($finalReport->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->download($finalReport->file_path, $finalReport->file_name);
    }

    public function view(FinalReport $finalReport)
    {
        $user = auth()->user();
        $isAdmin = $user && ($user->hasRole('admin') || ($user->isMentor() && optional($finalReport->user)->isVisibleTo($user)));

        if (!$isAdmin) {
            if (!$user || $user->id !== $finalReport->user_id) {
                abort(403);
            }
        }

        if (!Storage::disk('public')->exists($finalReport->file_path)) {
            abort(404);
        }

        $fullPath = Storage::disk('public')->path($finalReport->file_path);
        $mimeType = $finalReport->mime_type ?: 'application/pdf';

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $finalReport->file_name . '"',
        ]);
    }

    public function destroy(FinalReport $finalReport)
    {
        // Hapus berkas fisik (jika ada), lalu hapus datanya.
        if ($finalReport->file_path && Storage::disk('public')->exists($finalReport->file_path)) {
            Storage::disk('public')->delete($finalReport->file_path);
        }

        $finalReport->delete();

        flash()->success('Berhasil', 'Laporan akhir berhasil dihapus.');
        return redirect()->route('final-report.index');
    }

    public function exportMonthZip(string $month)
    {
        $reports = FinalReport::with(['user'])
            ->when(auth()->user()->isMentor(), fn ($q) => $q->whereIn('user_id', User::peserta()->inScope()->select('users.id')))
            ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month])
            ->orderBy('created_at', 'asc')
            ->get();

        if ($reports->isEmpty()) {
            flash()->error('Gagal', 'Tidak ada file pada bulan tersebut.');
            return redirect()->route('final-report.index');
        }

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $zipName = 'final-report-' . $month . '.zip';
        $zipPath = $tmpDir . DIRECTORY_SEPARATOR . $zipName;
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            flash()->error('Gagal', 'Gagal membuat file ZIP.');
            return redirect()->route('final-report.index');
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
