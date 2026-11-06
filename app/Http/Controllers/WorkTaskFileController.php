<?php

namespace App\Http\Controllers;

use App\Models\WorkTask;
use App\Models\WorkTaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Lihat / unduh lampiran referensi tugas & lampiran pekerjaan peserta dengan pengecekan akses. */
class WorkTaskFileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function reference(Request $request, WorkTask $task)
    {
        abort_unless($task->isVisibleTo(auth()->user()), 403);

        return $this->send($request, $task->reference_file_path, $task->reference_file_name);
    }

    public function attachment(Request $request, WorkTaskAttachment $attachment)
    {
        $viewer = auth()->user();
        $attachment->load('assignee.user', 'assignee.task.assignees.user');
        $assignee = $attachment->assignee;
        $task = $assignee->task;

        abort_unless($task->isVisibleTo($viewer), 403);

        if ($viewer->isMentor()) {
            abort_unless($assignee->user && $assignee->user->isVisibleTo($viewer), 403);
        } elseif (! $viewer->hasRole('admin')) {
            // Peserta: lampiran miliknya, atau lampiran bersama pada tugas mandiri.
            abort_unless((int) $assignee->user_id === (int) $viewer->id || $task->is_self, 403);
        }

        abort_if($attachment->isLink(), 404);

        return $this->send($request, $attachment->path, $attachment->name);
    }

    private function send(Request $request, ?string $path, ?string $name)
    {
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        $name = str_replace(['"', "\r", "\n"], '', $name ?: basename($path));

        if ($request->boolean('download')) {
            return Storage::disk('public')->download($path, $name);
        }

        return response()->file(Storage::disk('public')->path($path), [
            'Content-Disposition' => 'inline; filename="' . $name . '"',
        ]);
    }
}
