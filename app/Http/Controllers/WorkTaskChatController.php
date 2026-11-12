<?php

namespace App\Http\Controllers;

use App\Models\WorkTask;
use App\Models\WorkTaskMessage;
use App\Models\WorkTaskMessageFile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Ruang chat per tugas + riwayat pengeditan. Dipakai bersama oleh peserta dan mentor/admin. */
class WorkTaskChatController extends Controller
{
    private const FILE_MIMES = 'jpg,jpeg,png,gif,webp,mp4,webm,mov,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip';

    private $roleCache = [];

    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Daftar pesan: awal (terbaru), "after" (polling pesan baru + yang diedit), atau "before" (pesan lebih lama). */
    public function index(Request $request, WorkTask $task)
    {
        $this->authorizeTask($task);

        $query = $task->messages()->with(['user', 'files']);
        $hasMore = false;

        if ($request->filled('after')) {
            $after = (int) $request->query('after');
            $since = $this->parseTime($request->query('since'));

            $query->where(function ($q) use ($after, $since) {
                $q->where('id', '>', $after);
                if ($since) {
                    $q->orWhere('edited_at', '>', $since);
                }
            });
            $messages = $query->get();
        } else {
            $limit = 40;
            if ($request->filled('before')) {
                $query->where('id', '<', (int) $request->query('before'));
            }
            $rows = $query->reorder()->orderByDesc('id')->limit($limit + 1)->get();
            $hasMore = $rows->count() > $limit;
            $messages = $rows->take($limit)->reverse()->values();
        }

        return response()->json([
            'messages' => $messages->map(function ($m) { return $this->payload($m); })->values(),
            'has_more' => $hasMore,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function store(Request $request, WorkTask $task)
    {
        $this->authorizeTask($task);

        $data = $request->validate([
            'body' => 'nullable|string|max:4000',
            'files' => 'nullable|array|max:10',
            'files.*' => 'file|max:25600|mimes:' . self::FILE_MIMES,
        ], [
            'files.max' => 'Maksimal 10 file per pesan.',
            'files.*.max' => 'Ukuran tiap file maksimal 25 MB.',
            'files.*.mimes' => 'Format file tidak didukung.',
            'files.*.uploaded' => 'File gagal diunggah. Ukuran mungkin melebihi batas server.',
        ]);

        $body = trim((string) ($data['body'] ?? ''));
        $files = array_filter((array) $request->file('files', []));

        if ($body === '' && ! $files) {
            return response()->json(['message' => 'Tulis pesan atau lampirkan file.', 'errors' => ['body' => ['Tulis pesan atau lampirkan file.']]], 422);
        }

        $message = DB::transaction(function () use ($task, $body, $files) {
            $message = WorkTaskMessage::create([
                'work_task_id' => $task->id,
                'user_id' => auth()->id(),
                'kind' => WorkTaskMessage::TEXT,
                'body' => $body !== '' ? $body : null,
            ]);

            foreach ($files as $file) {
                $name = now()->format('YmdHis') . '-' . substr(md5(uniqid('', true)), 0, 8) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('work-chat/' . $task->id, $name, 'public');

                WorkTaskMessageFile::create([
                    'message_id' => $message->id,
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }

            return $message;
        });

        return response()->json(['message' => $this->payload($message->load(['user', 'files']))], 201);
    }

    /** Edit pesan: hanya pengirimnya, hanya isi teks. Pesan tidak bisa dihapus. */
    public function update(Request $request, WorkTask $task, WorkTaskMessage $message)
    {
        $this->authorizeTask($task);
        abort_unless((int) $message->work_task_id === (int) $task->id, 404);
        abort_unless($message->kind === WorkTaskMessage::TEXT && (int) $message->user_id === (int) auth()->id(), 403);

        $data = $request->validate(['body' => 'nullable|string|max:4000']);
        $body = trim((string) ($data['body'] ?? ''));

        if ($body === '' && $message->files()->doesntExist()) {
            return response()->json(['message' => 'Pesan tidak boleh kosong.', 'errors' => ['body' => ['Pesan tidak boleh kosong.']]], 422);
        }

        if ($body !== (string) $message->body) {
            $message->update(['body' => $body !== '' ? $body : null, 'edited_at' => now()]);
        }

        return response()->json(['message' => $this->payload($message->load(['user', 'files']))]);
    }

    /** Riwayat pengeditan tugas (popup "Riwayat"): terbaru di atas. */
    public function history(WorkTask $task)
    {
        $this->authorizeTask($task);

        $rows = $task->messages()->reorder()->where('kind', WorkTaskMessage::EDIT)->with('user')->orderByDesc('id')->limit(100)->get();

        return response()->json(['messages' => $rows->map(function ($m) { return $this->payload($m); })->values()]);
    }

    /** Tampilkan (inline, mendukung seek video) atau unduh file chat. */
    public function file(Request $request, WorkTaskMessageFile $file)
    {
        $file->load('message.task');
        $task = optional($file->message)->task;
        abort_unless($task && $task->isVisibleTo(auth()->user()), 403);
        abort_unless(Storage::disk('public')->exists($file->path), 404);

        $name = str_replace(['"', "\r", "\n"], '', $file->name ?: basename($file->path));

        if ($request->boolean('download') || $file->kind() === 'file') {
            return Storage::disk('public')->download($file->path, $name, ['X-Content-Type-Options' => 'nosniff']);
        }

        return response()->file(Storage::disk('public')->path($file->path), [
            'Content-Type' => $file->mime,
            'Content-Disposition' => 'inline; filename="' . $name . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizeTask(WorkTask $task): void
    {
        abort_unless($task->isVisibleTo(auth()->user()), 403);
    }

    private function parseTime($value): ?Carbon
    {
        try {
            return $value ? Carbon::parse($value) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function roleOf($user): string
    {
        if (! $user) {
            return 'peserta';
        }
        if (! isset($this->roleCache[$user->id])) {
            $this->roleCache[$user->id] = $user->hasAnyRole(['admin', 'mentor']) ? 'mentor' : 'peserta';
        }

        return $this->roleCache[$user->id];
    }

    private function payload(WorkTaskMessage $m): array
    {
        $viewerId = (int) auth()->id();

        return [
            'id' => $m->id,
            'kind' => $m->kind,
            'user' => [
                'id' => $m->user_id,
                'name' => optional($m->user)->name ?: 'Pengguna',
                'role' => $this->roleOf($m->user),
                'mine' => (int) $m->user_id === $viewerId,
            ],
            'body' => $m->body,
            'changes' => $m->kind === WorkTaskMessage::EDIT ? (array) $m->changes : [],
            'files' => $m->relationLoaded('files') ? $m->files->map(function ($f) {
                return [
                    'id' => $f->id,
                    'name' => $f->name,
                    'size' => (int) $f->size,
                    'type' => $f->kind(),
                    'url' => route('work-logs.chat.file', $f),
                    'download_url' => route('work-logs.chat.file', ['file' => $f, 'download' => 1]),
                ];
            })->values() : [],
            'edited' => (bool) $m->edited_at,
            'created_at' => $m->created_at->toIso8601String(),
        ];
    }
}
