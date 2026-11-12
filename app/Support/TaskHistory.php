<?php

namespace App\Support;

use App\Models\User;
use App\Models\WorkTask;
use App\Models\WorkTaskMessage;
use Illuminate\Support\Str;

/**
 * Mencatat perubahan (pengeditan) tugas sebagai pesan kind = edit di ruang chat tugas.
 * Daftar yang sama dipakai untuk popup "Riwayat".
 */
class TaskHistory
{
    /** Perubahan satu isian: ['label' => 'Judul', 'old' => '...', 'new' => '...']. */
    public static function change(string $label, $old, $new): array
    {
        return ['label' => $label, 'old' => self::clip($old), 'new' => self::clip($new)];
    }

    /** Perubahan berupa keterangan bebas, mis. "Menambah: a.pdf". */
    public static function note(string $label, string $text): array
    {
        return ['label' => $label, 'note' => self::clip($text, 400)];
    }

    /** Simpan catatan pengeditan. Tidak melakukan apa-apa bila tidak ada perubahan. */
    public static function record(WorkTask $task, ?User $actor, array $changes): ?WorkTaskMessage
    {
        $changes = array_values(array_filter($changes));
        if (! $changes) {
            return null;
        }

        $labels = array_map(function ($c) { return $c['label']; }, $changes);

        return WorkTaskMessage::create([
            'work_task_id' => $task->id,
            'user_id' => $actor ? $actor->id : null,
            'kind' => WorkTaskMessage::EDIT,
            'body' => 'Mengubah ' . mb_strtolower(implode(', ', $labels)),
            'changes' => $changes,
        ]);
    }

    private static function clip($value, int $limit = 300): string
    {
        $value = trim((string) $value);

        return $value === '' ? '-' : Str::limit($value, $limit);
    }
}
