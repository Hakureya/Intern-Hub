<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use App\Models\WorkTask;
use App\Models\WorkTaskAssignee;
use App\Models\WorkTaskAttachment;
use App\Models\WorkTaskReference;
use App\Support\TaskHistory;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

trait HandlesWorkTaskFiles
{
    protected function storeWorkFile(UploadedFile $file, string $dir, string $prefix): array
    {
        $name = $prefix . '-' . now()->format('YmdHis') . '-' . substr(md5(uniqid('', true)), 0, 6)
            . '.' . $file->getClientOriginalExtension();

        return [
            'path' => $file->storeAs('work-tasks/' . $dir, $name, 'public'),
            'name' => $file->getClientOriginalName(),
        ];
    }

    /** Hapus file fisik hanya jika tak dipakai lampiran mana pun. */
    protected function deleteFileIfUnused(?string $path): void
    {
        if ($path && ! WorkTaskAttachment::where('path', $path)->exists()) {
            Storage::disk('public')->delete($path);
        }
    }

    /** Tugas mandiri dikerjakan bersama: lampiran disinkronkan ke seluruh kelompok. */
    protected function attachmentTargets(WorkTask $task, WorkTaskAssignee $assignee)
    {
        return $task->is_self ? $task->assignees()->get() : collect([$assignee]);
    }

    /** Tambah lampiran berupa file (boleh banyak) dan/atau satu tautan. Mengembalikan jumlah yang ditambah. */
    protected function addAttachments(WorkTask $task, WorkTaskAssignee $assignee, array $files, ?string $link, ?string $label, array $links = []): int
    {
        $items = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $stored = $this->storeWorkFile($file, 'proof', 'proof-' . $assignee->user_id);
                $items[] = ['type' => 'file', 'name' => $stored['name'], 'path' => $stored['path'], 'url' => null];
            }
        }

        // satu tautan (dari form detail) + daftar tautan (dari form tambah tugas)
        $allLinks = $links;
        if ($link) {
            array_unshift($allLinks, ['url' => $link, 'label' => $label]);
        }
        foreach ($allLinks as $l) {
            $items[] = [
                'type' => 'link',
                'name' => $l['label'] ?: (parse_url($l['url'], PHP_URL_HOST) ?: $l['url']),
                'path' => null,
                'url' => $l['url'],
            ];
        }

        foreach ($this->attachmentTargets($task, $assignee) as $target) {
            foreach ($items as $item) {
                WorkTaskAttachment::create($item + ['work_task_assignee_id' => $target->id, 'uploaded_by' => auth()->id()]);
            }
        }

        return count($items);
    }

    protected function removeAttachment(WorkTask $task, WorkTaskAttachment $attachment): void
    {
        $ids = $this->attachmentTargets($task, $attachment->assignee)->pluck('id');

        $siblings = WorkTaskAttachment::whereIn('work_task_assignee_id', $ids)
            ->where('type', $attachment->type)
            ->where($attachment->isLink() ? 'url' : 'path', $attachment->isLink() ? $attachment->url : $attachment->path)
            ->get();

        $paths = $siblings->pluck('path')->filter()->unique();
        WorkTaskAttachment::whereIn('id', $siblings->pluck('id'))->delete();

        foreach ($paths as $path) {
            $this->deleteFileIfUnused($path);
        }
    }

    protected function copyAttachments(WorkTaskAssignee $from, WorkTaskAssignee $to): void
    {
        foreach ($from->attachments as $a) {
            WorkTaskAttachment::create([
                'work_task_assignee_id' => $to->id,
                'type' => $a->type,
                'name' => $a->name,
                'path' => $a->path,
                'url' => $a->url,
                'uploaded_by' => $a->uploaded_by, // salinan tetap tercatat diunggah oleh pengunggah aslinya
            ]);
        }
    }

    /** Pasangkan daftar url + label (urut sama) menjadi [['url' => ..., 'label' => ...]]; kosong & duplikat dibuang. */
    protected function pairLinks($urls, $labels): array
    {
        $urls = array_values((array) $urls);
        $labels = array_values((array) $labels);
        $pairs = [];
        $seen = [];

        foreach ($urls as $i => $url) {
            $url = trim((string) $url);
            if ($url === '' || isset($seen[$url])) {
                continue;
            }
            $seen[$url] = true;
            $label = isset($labels[$i]) ? trim((string) $labels[$i]) : '';
            $pairs[] = ['url' => $url, 'label' => $label !== '' ? $label : null];
        }

        return $pairs;
    }

    /** Simpan file referensi ke disk; mengembalikan [['name' => ..., 'path' => ...], ...]. */
    protected function storeReferenceFiles(array $files): array
    {
        $stored = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $stored[] = $this->storeWorkFile($file, 'reference', 'ref');
            }
        }

        return $stored;
    }

    /** Tambah referensi tugas: banyak file (sudah tersimpan) dan banyak tautan. */
    protected function addReferences(WorkTask $task, array $storedFiles, array $links): void
    {
        foreach ($storedFiles as $file) {
            WorkTaskReference::create(['work_task_id' => $task->id, 'type' => 'file', 'name' => $file['name'], 'path' => $file['path'], 'url' => null, 'uploaded_by' => auth()->id()]);
        }
        foreach ($links as $l) {
            WorkTaskReference::create([
                'work_task_id' => $task->id,
                'type' => 'link',
                'name' => $l['label'] ?: (parse_url($l['url'], PHP_URL_HOST) ?: $l['url']),
                'path' => null,
                'url' => $l['url'],
                'uploaded_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Tambah referensi cepat dari halaman detail (file dan/atau satu tautan), dipakai bersama oleh peserta dan mentor/admin.
     * Tidak mengubah status penyelesaian; tercatat di Riwayat. Mengembalikan jumlah referensi yang ditambahkan.
     */
    protected function quickAddReferences(Request $request, WorkTask $task, User $actor): int
    {
        $request->validate([
            'reference_files' => 'nullable|array|max:10',
            'reference_files.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip',
            'reference_link' => 'nullable|url|max:500',
            'reference_link_label' => 'nullable|string|max:120',
        ], [
            'reference_files.max' => 'Maksimal 10 file referensi sekali unggah.',
            'reference_files.*.max' => 'Ukuran tiap file maksimal 10 MB.',
            'reference_files.*.mimes' => 'Format file harus pdf, doc, xls, ppt, jpg, png, atau zip.',
            'reference_files.*.uploaded' => 'File gagal diunggah. Pastikan ukuran tidak melebihi batas server.',
            'reference_link.url' => 'Tautan harus diawali http:// atau https://.',
        ]);

        if (! $request->hasFile('reference_files') && ! $request->filled('reference_link')) {
            throw ValidationException::withMessages(['reference_files' => 'Pilih file atau isi tautan referensi terlebih dahulu.']);
        }

        $storedFiles = $this->storeReferenceFiles((array) $request->file('reference_files', []));
        $links = $this->pairLinks(
            $request->filled('reference_link') ? [$request->input('reference_link')] : [],
            [$request->input('reference_link_label')]
        );

        $this->addReferences($task, $storedFiles, $links);

        $added = array_merge(
            array_map(function ($f) { return $f['name']; }, $storedFiles),
            array_map(function ($l) { return $l['label'] ?: $l['url']; }, $links)
        );
        TaskHistory::record($task, $actor, [TaskHistory::note('Lampiran referensi', 'Menambah: ' . implode(', ', $added) . '.')]);

        return count($added);
    }

    /** Hapus satu referensi (beserta file fisiknya bila tak dipakai lagi) dan catat di Riwayat. */
    protected function quickRemoveReference(WorkTask $task, WorkTaskReference $reference, User $actor): void
    {
        $name = $reference->name;
        $path = $reference->path;
        $uploader = $reference->uploader;
        $suffix = ($uploader && (int) $uploader->id !== (int) $actor->id) ? ' (diunggah ' . $uploader->name . ')' : '';
        $reference->delete();
        $this->deleteReferenceFileIfUnused($path);

        TaskHistory::record($task, $actor, [TaskHistory::note('Lampiran referensi', 'Menghapus: ' . $name . $suffix . '.')]);
    }

    protected function deleteReferenceFileIfUnused(?string $path): void
    {
        if ($path && ! WorkTaskReference::where('path', $path)->exists()) {
            Storage::disk('public')->delete($path);
        }
    }

    protected function monthParam($request): string
    {
        $month = (string) $request->input('month', '');

        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) ? $month : now()->format('Y-m');
    }
}
