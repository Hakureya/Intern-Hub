<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesWorkTaskFiles;
use App\Models\Division;
use App\Models\User;
use App\Models\WorkTask;
use App\Models\WorkTaskAssignee;
use App\Models\WorkTaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Log pekerjaan sisi admin & mentor (mentor hanya melihat/mengelola divisinya). */
class WorkTaskController extends Controller
{
    use HandlesWorkTaskFiles;

    private const FILE_MIMES = 'pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip';

    public function index(Request $request)
    {
        $viewer = auth()->user();
        $month = $this->monthParam($request);
        $search = trim((string) $request->input('q', ''));
        $divisionId = $viewer->isMentor() ? 0 : (int) $request->input('division_id');

        $participants = User::peserta()->inScope($viewer)->with('division')->orderByName()->get();
        $userId = (int) $request->input('user_id');
        if ($userId && ! $participants->contains('id', $userId)) {
            $userId = 0;
        }

        $tasks = WorkTask::with(['assignees.user.division', 'creator'])
            ->visibleTo($viewer)
            ->inMonth($month)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('work_tasks.title', 'like', '%' . $search . '%')
                        ->orWhere('work_tasks.description', 'like', '%' . $search . '%');
                });
            })
            ->when($userId, function ($q) use ($userId) {
                $q->whereHas('assignees', function ($a) use ($userId) {
                    $a->where('user_id', $userId);
                });
            })
            ->when($divisionId, function ($q) use ($divisionId) {
                $q->whereHas('assignees.user', function ($u) use ($divisionId) {
                    $u->where('users.division_id', $divisionId);
                });
            })
            ->orderByDesc('work_tasks.created_at')
            ->orderByDesc('work_tasks.id')
            ->get();

        $rows = $tasks->map(function ($task) use ($viewer) {
            $assignees = $task->assigneesFor($viewer);

            return [
                'task' => $task,
                'assignees' => $assignees,
                'people' => $assignees->count() > 1
                    ? $assignees->map(function ($a) use ($viewer) {
                        return WorkTask::cardPayload($a->user, $viewer);
                    })->values()->all()
                    : [],
                'counts' => [
                    'belum' => $assignees->where('status', WorkTaskAssignee::BELUM)->count(),
                    'menunggu' => $assignees->where('status', WorkTaskAssignee::MENUNGGU)->count(),
                    'sudah' => $assignees->where('status', WorkTaskAssignee::SUDAH)->count(),
                ],
            ];
        });

        // Kartu dikelompokkan per tanggal dibuat (terbaru di atas).
        $groups = $rows->groupBy(function ($r) {
            return $r['task']->created_at->toDateString();
        });

        return view('admin.work-logs.index', [
            'rows' => $rows,
            'groups' => $groups,
            'month' => $month,
            'search' => $search,
            'userId' => $userId,
            'divisionId' => $divisionId,
            'participants' => $participants,
            'divisions' => $viewer->isMentor() ? collect() : Division::orderBy('name')->get(),
            'isMentor' => $viewer->isMentor(),
            'viewer' => $viewer,
        ]);
    }

    public function create()
    {
        $viewer = auth()->user();

        return view('admin.work-logs.form', [
            'task' => null,
            'participants' => User::peserta()->inScope($viewer)->with('division')->orderByName()->get(),
            'isMentor' => $viewer->isMentor(),
        ]);
    }

    public function store(Request $request)
    {
        $viewer = auth()->user();

        $data = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'required|string|max:5000',
            'deadline' => 'nullable|date|after_or_equal:today',
            'reference_file' => 'nullable|file|max:10240|mimes:' . self::FILE_MIMES,
            'reference_link' => 'nullable|url|max:500',
            'require_attachment' => 'required|in:0,1',
            'participants' => 'required|array|min:1',
            'participants.*' => 'integer',
        ], [
            'participants.required' => 'Pilih minimal satu peserta magang.',
            'participants.min' => 'Pilih minimal satu peserta magang.',
            'deadline.after_or_equal' => 'Tenggat tidak boleh sebelum hari ini.',
            'reference_link.url' => 'Tautan lampiran harus diawali http:// atau https://.',
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['participants'])));
        $allowed = User::peserta()->inScope($viewer)->whereIn('users.id', $ids)->pluck('users.id')->all();

        if (count($allowed) !== count($ids)) {
            return back()->withInput()->withErrors([
                'participants' => 'Ada peserta yang tidak valid atau di luar divisi Anda.',
            ]);
        }

        $file = $request->hasFile('reference_file')
            ? $this->storeWorkFile($request->file('reference_file'), 'reference', 'ref')
            : null;

        DB::transaction(function () use ($data, $viewer, $file, $allowed) {
            $task = WorkTask::create([
                'title' => trim($data['title']),
                'description' => trim($data['description']),
                'deadline' => $data['deadline'] ?? null,
                'require_attachment' => (bool) $data['require_attachment'],
                'reference_link' => $data['reference_link'] ?? null,
                'reference_file_path' => $file['path'] ?? null,
                'reference_file_name' => $file['name'] ?? null,
                'is_self' => false,
                'created_by' => $viewer->id,
            ]);

            foreach ($allowed as $userId) {
                WorkTaskAssignee::create([
                    'work_task_id' => $task->id,
                    'user_id' => $userId,
                    'status' => WorkTaskAssignee::BELUM,
                ]);
            }
        });

        flash()->success('Berhasil', 'Tugas baru berhasil dibuat untuk ' . count($allowed) . ' peserta.');

        return redirect()->route('work-logs.index', ['month' => now()->format('Y-m')]);
    }

    public function show(WorkTask $task)
    {
        $viewer = auth()->user();
        abort_unless($task->isVisibleTo($viewer), 403);

        $task->load('creator', 'assignees.attachments');
        $assignees = $task->assigneesFor($viewer);

        return view('admin.work-logs.show', [
            'task' => $task,
            'assignees' => $assignees,
            'people' => $assignees->count() > 1
                ? $assignees->map(function ($a) use ($viewer) {
                    return WorkTask::cardPayload($a->user, $viewer);
                })->values()->all()
                : [],
            'canEdit' => $task->canBeEditedBy($viewer),
        ]);
    }

    public function edit(WorkTask $task)
    {
        $viewer = auth()->user();
        abort_unless($task->isVisibleTo($viewer), 403);

        if (! $task->canBeEditedBy($viewer)) {
            flash()->error('Tidak bisa diedit', 'Tugas hanya bisa diedit oleh pembuatnya dan sebelum tenggat.');

            return redirect()->route('work-logs.show', $task);
        }

        return view('admin.work-logs.form', [
            'task' => $task,
            'participants' => User::peserta()->inScope($viewer)->with('division')->orderByName()->get(),
            'currentIds' => $task->assigneesFor($viewer)->pluck('user_id')->all(),
            'isMentor' => $viewer->isMentor(),
        ]);
    }

    public function update(Request $request, WorkTask $task)
    {
        $viewer = auth()->user();
        abort_unless($task->isVisibleTo($viewer), 403);

        if (! $task->canBeEditedBy($viewer)) {
            flash()->error('Tidak bisa diedit', 'Tugas hanya bisa diedit oleh pembuatnya dan sebelum tenggat.');

            return redirect()->route('work-logs.show', $task);
        }

        $data = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'required|string|max:5000',
            'deadline' => 'nullable|date',
            'reference_file' => 'nullable|file|max:10240|mimes:' . self::FILE_MIMES,
            'reference_link' => 'nullable|url|max:500',
            'require_attachment' => 'required|in:0,1',
            'remove_reference_file' => 'nullable|boolean',
            'participants' => 'required|array|min:1',
            'participants.*' => 'integer',
        ], [
            'participants.required' => 'Pilih minimal satu peserta magang.',
            'participants.min' => 'Pilih minimal satu peserta magang.',
        ]);

        // Peserta yang boleh dikelola viewer (mentor: hanya divisinya).
        $allowedIds = User::peserta()->inScope($viewer)->pluck('users.id')->map(function ($id) { return (int) $id; })->all();
        $selected = array_values(array_unique(array_map('intval', $data['participants'])));

        if (array_diff($selected, $allowedIds)) {
            return back()->withInput()->withErrors([
                'participants' => 'Ada peserta yang tidak valid atau di luar divisi Anda.',
            ]);
        }

        $task->load('assignees');
        $currentAll = $task->assignees->pluck('user_id')->map(function ($id) { return (int) $id; })->all();
        $currentManaged = array_values(array_intersect($currentAll, $allowedIds));
        $toAdd = array_values(array_diff($selected, $currentAll));
        $toRemove = array_values(array_diff($currentManaged, $selected));

        $update = [
            'title' => trim($data['title']),
            'description' => trim($data['description']),
            'deadline' => $data['deadline'] ?? null,
            'require_attachment' => (bool) $data['require_attachment'],
            'reference_link' => $data['reference_link'] ?? null,
        ];

        $oldReference = $task->reference_file_path;

        if ($request->hasFile('reference_file')) {
            $file = $this->storeWorkFile($request->file('reference_file'), 'reference', 'ref');
            $update['reference_file_path'] = $file['path'];
            $update['reference_file_name'] = $file['name'];
        } elseif ($request->boolean('remove_reference_file')) {
            $update['reference_file_path'] = null;
            $update['reference_file_name'] = null;
        }

        $removedPaths = [];

        DB::transaction(function () use ($task, $update, $toAdd, $toRemove, &$removedPaths) {
            $task->update($update);

            // Ubah daftar peserta: peserta yang dikeluarkan ikut kehilangan lampirannya.
            if ($toRemove) {
                $ids = $task->assignees()->whereIn('user_id', $toRemove)->pluck('id');
                $removedPaths = WorkTaskAttachment::whereIn('work_task_assignee_id', $ids)->whereNotNull('path')->pluck('path')->all();
                $task->assignees()->whereIn('user_id', $toRemove)->delete();
            }
            foreach ($toAdd as $userId) {
                WorkTaskAssignee::create([
                    'work_task_id' => $task->id,
                    'user_id' => $userId,
                    'status' => WorkTaskAssignee::BELUM,
                ]);
            }

            // Setiap pengeditan: status seluruh peserta kembali "belum" (lampiran tetap).
            $task->resetAssigneeStatuses();
        });

        foreach (array_unique($removedPaths) as $path) {
            $this->deleteFileIfUnused($path);
        }
        if (array_key_exists('reference_file_path', $update)) {
            $this->deleteReferenceIfUnused($oldReference, $task->id);
        }

        flash()->success('Berhasil', 'Tugas diperbarui. Status seluruh peserta dikembalikan menjadi Belum.');

        return redirect()->route('work-logs.show', $task);
    }

    /** Mentor/admin mengubah status peserta: belum / menunggu / sudah. */
    public function updateStatus(Request $request, WorkTask $task, WorkTaskAssignee $assignee)
    {
        $viewer = auth()->user();

        abort_unless((int) $assignee->work_task_id === (int) $task->id, 404);
        abort_unless($task->isVisibleTo($viewer), 403);
        abort_unless($task->assigneesFor($viewer)->contains('id', $assignee->id), 403);

        $data = $request->validate([
            'status' => 'required|in:belum,menunggu,sudah',
            'review_note' => 'nullable|string|max:500',
        ]);

        if ($assignee->status === $data['status']) {
            flash()->error('Tidak ada perubahan', 'Status peserta sudah ' . WorkTaskAssignee::LABELS[$data['status']] . '.');

            return redirect()->route('work-logs.show', $task);
        }

        $note = $data['review_note'] ?? null;

        if ($data['status'] === WorkTaskAssignee::SUDAH) {
            $assignee->update([
                'status' => WorkTaskAssignee::SUDAH,
                'submitted_at' => $assignee->submitted_at ?: now(),
                'reviewed_at' => now(),
                'reviewed_by' => $viewer->id,
                'review_note' => $note,
            ]);
        } elseif ($data['status'] === WorkTaskAssignee::MENUNGGU) {
            $assignee->update([
                'status' => WorkTaskAssignee::MENUNGGU,
                'submitted_at' => $assignee->submitted_at ?: now(),
                'reviewed_at' => null,
                'reviewed_by' => null,
                'review_note' => $note,
            ]);
        } else {
            // Kembali ke "belum": lampiran peserta TETAP tersimpan, peserta bisa mengganti/menambah lalu mengajukan lagi.
            $assignee->update([
                'status' => WorkTaskAssignee::BELUM,
                'submitted_at' => null,
                'reviewed_at' => now(),
                'reviewed_by' => $viewer->id,
                'review_note' => $note,
            ]);
        }

        flash()->success('Berhasil', 'Status ' . optional($assignee->user)->name . ' diubah menjadi ' . WorkTaskAssignee::LABELS[$data['status']] . '.');

        return redirect()->route('work-logs.show', $task);
    }
}
