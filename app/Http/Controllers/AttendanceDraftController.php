<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDraftDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttendanceDraftController extends Controller
{
    public function adminIndex(Request $request)
    {
        $selectedMonth = $request->query('month', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = now()->format('Y-m');
        }

        $selectedUserId = $request->query('user_id');
        $users = User::orderByName()->get();

        $query = AttendanceDraftDocument::with(['user', 'uploadedBy'])
            ->where('report_month', $selectedMonth)
            ->orderBy('created_at', 'desc');

        if ($selectedUserId) {
            $query->where('user_id', $selectedUserId);
        }

        $documents = $query->get();

        return view('admin.attendance-drafts')->with([
            'documents' => $documents,
            'selectedMonth' => $selectedMonth,
            'users' => $users,
            'selectedUserId' => $selectedUserId,
        ]);
    }

    public function userIndex()
    {
        $user = auth()->user();

        $documents = collect();
        if ($user) {
            $documents = AttendanceDraftDocument::where('user_id', $user->id)
                ->orderBy('report_month', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('user.attendance-draft')->with([
            'user' => $user,
            'documents' => $documents,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'report_month' => 'required|date_format:Y-m',
            'draft_document' => 'required|file|max:5120|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
        ]);

        $user = auth()->user();

        $file = $request->file('draft_document');
        $timestamp = now()->format('YmdHis');
        $extension = $file->getClientOriginalExtension();
        $safeName = 'attendance-draft-' . $user->id . '-' . $timestamp . '.' . $extension;
        $month = $request->report_month;
        $path = $file->storeAs('attendance-drafts/' . $month, $safeName, 'public');

        AttendanceDraftDocument::create([
            'user_id' => $user->id,
            'uploaded_by' => $user->id,
            'report_month' => $month,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
        ]);

        flash()->success('Berhasil', 'Draft dokumen presensi berhasil diunggah.');
        return redirect()->route('user.attendance.draft');
    }

    public function view(AttendanceDraftDocument $document)
    {
        $this->authorizeAccess($document);
        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404);
        }

        $fullPath = Storage::disk('public')->path($document->file_path);
        $mimeType = $document->mime_type ?: 'application/octet-stream';

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $document->file_name . '"',
        ]);
    }

    public function download(AttendanceDraftDocument $document)
    {
        $this->authorizeAccess($document);
        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    private function authorizeAccess(AttendanceDraftDocument $document): void
    {
        $user = auth()->user();
        if ($user && $user->hasRole('admin')) {
            return;
        }

        if (!$user || (int) $user->id !== (int) $document->user_id) {
            abort(403);
        }
    }
}
