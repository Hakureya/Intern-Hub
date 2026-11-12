<?php

namespace App\Http\Controllers\Concerns;

use App\Models\WorkTask;
use App\Models\WorkTaskAssignee;
use App\Models\WorkTaskAttachment;
use App\Models\WorkTaskReference;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
                WorkTaskAttachment::create($item + ['work_task_assignee_id' => $target->id]);
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
            WorkTaskReference::create(['work_task_id' => $task->id, 'type' => 'file', 'name' => $file['name'], 'path' => $file['path'], 'url' => null]);
        }
        foreach ($links as $l) {
            WorkTaskReference::create([
                'work_task_id' => $task->id,
                'type' => 'link',
                'name' => $l['label'] ?: (parse_url($l['url'], PHP_URL_HOST) ?: $l['url']),
                'path' => null,
                'url' => $l['url'],
            ]);
        }
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
