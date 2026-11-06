<?php

namespace App\Http\Controllers\Concerns;

use App\Models\WorkTask;
use App\Models\WorkTaskAssignee;
use App\Models\WorkTaskAttachment;
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
    protected function addAttachments(WorkTask $task, WorkTaskAssignee $assignee, array $files, ?string $link, ?string $label): int
    {
        $items = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $stored = $this->storeWorkFile($file, 'proof', 'proof-' . $assignee->user_id);
                $items[] = ['type' => 'file', 'name' => $stored['name'], 'path' => $stored['path'], 'url' => null];
            }
        }

        if ($link) {
            $items[] = [
                'type' => 'link',
                'name' => $label ?: (parse_url($link, PHP_URL_HOST) ?: $link),
                'path' => null,
                'url' => $link,
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

    protected function deleteReferenceIfUnused(?string $path, int $exceptTaskId): void
    {
        if ($path && ! WorkTask::where('reference_file_path', $path)->where('id', '!=', $exceptTaskId)->exists()) {
            Storage::disk('public')->delete($path);
        }
    }

    protected function monthParam($request): string
    {
        $month = (string) $request->input('month', '');

        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) ? $month : now()->format('Y-m');
    }
}
