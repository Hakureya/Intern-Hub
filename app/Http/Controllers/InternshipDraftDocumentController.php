<?php

namespace App\Http\Controllers;

use App\Models\InternshipDraftDocument;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class InternshipDraftDocumentController extends Controller
{
    public function index(Request $request)
    {
        $selectedType = $request->query('type');
        $allowedTypes = ['monthly', 'final', 'dossier'];
        if (!in_array($selectedType, $allowedTypes, true)) {
            $selectedType = '';
        }

        $query = InternshipDraftDocument::with('uploader')->orderBy('created_at', 'desc');
        if ($selectedType !== '') {
            $query->where('document_type', $selectedType);
        }

        $documents = $query->get();

        return view('admin.internship-draft-documents', [
            'documents' => $documents,
            'selectedType' => $selectedType,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'document_type' => 'required|in:monthly,final,dossier',
            'library_category' => [
                'nullable',
                Rule::in(['compro', 'laporan_keuangan', 'pedoman_sop', 'materi_orientasi', 'lainnya']),
            ],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'draft_document' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png',
        ]);

        if ($request->document_type === 'dossier' && !$request->filled('library_category')) {
            return back()
                ->withErrors(['library_category' => 'Kategori perpustakaan wajib dipilih untuk dokumen dossier.'])
                ->withInput();
        }

        $file = $request->file('draft_document');
        $type = $request->document_type;
        $timestamp = now()->format('YmdHis');
        $extension = $file->getClientOriginalExtension();
        $safeName = 'internship-draft-' . $type . '-' . $timestamp . '.' . $extension;
        $path = $file->storeAs('internship-draft-documents/' . $type, $safeName, 'public');

        InternshipDraftDocument::create([
            'uploaded_by' => auth()->id(),
            'document_type' => $type,
            'library_category' => $type === 'dossier' ? $request->library_category : null,
            'title' => $request->title,
            'description' => $request->description,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
        ]);

        flash()->success('Berhasil', 'Draft dokumen magang berhasil ditambahkan.');
        return redirect()->route('internship-draft-documents.index');
    }

    public function view(InternshipDraftDocument $document)
    {
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

    public function download(InternshipDraftDocument $document)
    {
        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    public function destroy(InternshipDraftDocument $document)
    {
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();
        flash()->success('Berhasil', 'Draft dokumen magang berhasil dihapus.');
        return redirect()->route('internship-draft-documents.index');
    }
}
