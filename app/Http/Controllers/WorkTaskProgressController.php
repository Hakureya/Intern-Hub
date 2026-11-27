<?php

namespace App\Http\Controllers;

use App\Models\WorkTask;
use App\Support\TaskHistory;
use App\Models\WorkTaskProgress;
use Illuminate\Http\Request;

/** Turunan tugas (tanggal pengerjaan + Total selesai) atau catatan progres bertahap. Satu tugas tetap satu ID. */
class WorkTaskProgressController extends Controller
{
    public function store(Request $request, WorkTask $task)
    {
        $me = auth()->user();
        abort_unless($task->canAddProgressBy($me), 403);

        // Tugas dengan turunan: tiap turunan = tanggal pengerjaan + Total selesai (wajib). Tugas lain: catatan teks saja.
        $children = (bool) $task->has_children;

        $data = $request->validate([
            'entry_date' => 'required|date|before_or_equal:today',
            'quantity' => $children ? 'required|integer|min:1|max:1000000' : 'nullable',
            'note' => 'nullable|string|max:1000',
        ], [
            'entry_date.required' => 'Tanggal pengerjaan wajib diisi.',
            'entry_date.before_or_equal' => 'Tanggal pengerjaan tidak boleh melewati hari ini.',
            'quantity.required' => 'Total selesai wajib diisi.',
            'quantity.integer' => 'Total selesai harus berupa angka bulat.',
            'quantity.min' => 'Total selesai minimal 1.',
            'note.max' => 'Catatan maksimal 1000 karakter.',
        ]);

        $qty = $children ? (int) $data['quantity'] : null;
        $note = trim((string) ($data['note'] ?? ''));

        if (! $children && $note === '') {
            return back()->withInput()->withErrors(['note' => 'Isi catatan terlebih dulu.']);
        }

        $entry = WorkTaskProgress::create([
            'work_task_id' => $task->id,
            'user_id' => $me->id,
            'entry_date' => $data['entry_date'],
            'quantity' => $qty,
            'note' => $note !== '' ? $note : null,
        ]);

        // Masuk ke riwayat tugas (popup "Riwayat").
        TaskHistory::record($task, $me, [TaskHistory::note($children ? 'Turunan tugas' : 'Catatan progres', 'Menambah: ' . $this->describe($task, $entry, $me))]);

        flash()->success('Berhasil', $children ? 'Turunan tugas ditambahkan.' : 'Catatan progres ditambahkan.');

        return back();
    }

    public function destroy(WorkTask $task, WorkTaskProgress $progress)
    {
        abort_unless((int) $progress->work_task_id === (int) $task->id, 404);
        abort_unless($task->canRemoveProgressBy(auth()->user(), $progress), 403);

        $me = auth()->user();
        $progress->loadMissing('user');
        $label = $task->has_children ? 'Turunan tugas' : 'Catatan progres';
        $text = 'Menghapus: ' . $this->describe($task, $progress, $me);

        $progress->delete();
        TaskHistory::record($task, $me, [TaskHistory::note($label, $text)]);

        flash()->success('Berhasil', $task->has_children ? 'Turunan tugas dihapus.' : 'Catatan progres dihapus.');

        return back();
    }

    /** Ringkasan satu turunan/catatan untuk riwayat: "25 Nov 2026 — 120 Bantex — "catatan" (milik Nama)". */
    private function describe(WorkTask $task, WorkTaskProgress $entry, $actor): string
    {
        $parts = [$entry->entry_date->locale('id')->translatedFormat('d M Y')];
        if ($entry->quantity !== null) {
            $parts[] = number_format($entry->quantity, 0, ',', '.') . ' ' . $task->quantity_unit;
        }
        if ($entry->note) {
            $parts[] = '"' . \Illuminate\Support\Str::limit($entry->note, 80) . '"';
        }
        $text = implode(' — ', $parts);

        // Bila yang menghapus bukan pemilik catatan, sebut pemiliknya.
        if ((int) $entry->user_id !== (int) $actor->id && $entry->user) {
            $text .= ' (milik ' . $entry->user->name . ')';
        }

        return $text;
    }
}
