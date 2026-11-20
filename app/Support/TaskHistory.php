<?php

namespace App\Support;

use App\Models\User;
use App\Models\WorkTask;
use App\Models\WorkTaskAssignee;
use App\Models\WorkTaskMessage;
use Illuminate\Support\Str;

/**
 * Mencatat perubahan tugas (pengeditan dan perubahan status) sebagai pesan kind = edit.
 * Catatan ini hanya ditampilkan di popup "Riwayat", tidak di chat diskusi.
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

    /**
     * Perubahan status peserta. $moves: [['name' => 'Budi', 'old' => 'belum', 'new' => 'batal'], ...].
     * Perubahan yang sama (lama -> baru) digabung jadi satu baris; nama peserta ditulis bila tugas punya lebih dari satu peserta.
     */
    public static function statusChanges(WorkTask $task, array $moves): array
    {
        $task->loadMissing('assignees');
        $multi = $task->assignees->count() > 1;

        $groups = [];
        foreach ($moves as $m) {
            if ($m['old'] === $m['new']) {
                continue;
            }
            $groups[$m['old'] . '>' . $m['new']][] = $m;
        }

        $out = [];
        foreach ($groups as $list) {
            $names = array_values(array_filter(array_map(function ($m) { return $m['name']; }, $list)));
            $label = $multi && $names ? 'Status (' . implode(', ', $names) . ')' : 'Status';
            $out[] = self::change(
                $label,
                WorkTaskAssignee::LABELS[$list[0]['old']] ?? $list[0]['old'],
                WorkTaskAssignee::LABELS[$list[0]['new']] ?? $list[0]['new']
            );
        }

        return $out;
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
