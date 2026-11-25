<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesWorkTaskFiles;
use App\Models\Division;
use App\Models\User;
use App\Models\WorkCategory;
use App\Support\TaskHistory;
use App\Models\WorkTask;
use App\Models\WorkTaskAssignee;
use App\Models\WorkTaskReference;
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
        $status = (string) $request->input('status', '');
        $status = in_array($status, ['belum', 'menunggu', 'sudah', 'batal'], true) ? $status : '';
        $divisionId = $viewer->isMentor() ? 0 : (int) $request->input('division_id');
        $type = WorkTask::typeParam($request->input('type'));

        $participants = User::peserta()->inScope($viewer)->with('division')->orderByName()->get();
        $userId = (int) $request->input('user_id');
        if ($userId && ! $participants->contains('id', $userId)) {
            $userId = 0;
        }

        $tasks = WorkTask::with(['assignees.user.division', 'assignees.user.mentor', 'creator'])
            ->withSum('progress as progress_sum_quantity', 'quantity')
            ->visibleTo($viewer)
            ->inMonth($month)
            ->where('work_tasks.type', $type)
            ->when($search !== '', function ($q) use ($search) {
                $q->search($search);
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
            ->orderByRaw('COALESCE(work_tasks.start_date, work_tasks.worked_on, DATE(work_tasks.created_at)) desc')
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
                    'batal' => $assignees->where('status', WorkTaskAssignee::BATAL)->count(),
                ],
                'allCancelled' => $assignees->isNotEmpty() && $assignees->every(function ($a) {
                    return $a->status === WorkTaskAssignee::BATAL;
                }),
            ];
        });

        // Ringkasan filter status: jumlah tugas yang memiliki minimal satu peserta dengan status tsb
        // (mengikuti bulan, pencarian, peserta, dan divisi yang dipilih; tidak mengikuti filter status).
        // Jumlah tugas per bentuk (badge pada tab proyek / harian), mengikuti bulan.
        $typeCounts = [
            WorkTask::TYPE_PROJECT => WorkTask::visibleTo($viewer)->inMonth($month)->where('work_tasks.type', WorkTask::TYPE_PROJECT)->count(),
            WorkTask::TYPE_DAILY => WorkTask::visibleTo($viewer)->inMonth($month)->where('work_tasks.type', WorkTask::TYPE_DAILY)->count(),
        ];

        $stats = [
            'semua' => $rows->count(),
            'sudah' => $rows->filter(function ($r) { return $r['counts']['sudah'] > 0; })->count(),
            'menunggu' => $rows->filter(function ($r) { return $r['counts']['menunggu'] > 0; })->count(),
            'belum' => $rows->filter(function ($r) { return $r['counts']['belum'] > 0; })->count(),
            'batal' => $rows->filter(function ($r) { return $r['counts']['batal'] > 0; })->count(),
        ];

        if ($status !== '') {
            $rows = $rows->filter(function ($r) use ($status) {
                return $r['counts'][$status] > 0;
            })->values();
        }

        // Kartu dikelompokkan per tanggal mulai (tugas mentor: tanggal dibuat, tugas mandiri: tanggal mulai isian peserta), terbaru di atas.
        $groups = $rows->groupBy(function ($r) {
            return $r['task']->workedDate()->toDateString();
        });

        return view('admin.work-logs.index', [
            'rows' => $rows,
            'groups' => $groups,
            'month' => $month,
            'search' => $search,
            'status' => $status,
            'type' => $type,
            'typeCounts' => $typeCounts,
            'stats' => $stats,
            'userId' => $userId,
            'divisionId' => $divisionId,
            'participants' => $participants,
            'divisions' => $viewer->isMentor() ? collect() : Division::orderBy('name')->get(),
            'isMentor' => $viewer->isMentor(),
            'viewer' => $viewer,
        ]);
    }

    /** Pilihan kategori untuk form tugas harian: mentor = divisinya, admin = per divisi (optgroup). */
    private function categoryGroups(User $viewer): array
    {
        if ($viewer->isMentor()) {
            return [optional($viewer->division)->name ?: 'Divisi' => WorkCategory::namesFor($viewer->division_id)];
        }
        $groups = [];
        foreach (Division::orderBy('name')->get() as $d) {
            $groups[$d->name] = WorkCategory::namesFor($d->id);
        }

        return $groups;
    }

    private function dailyFormData(User $viewer, ?WorkTask $task): array
    {
        return [
            'task' => $task,
            'participants' => User::peserta()->inScope($viewer)->with(['division', 'mentor'])->orderByName()->get(),
            'currentIds' => $task ? $task->assigneesFor($viewer)->pluck('user_id')->all() : [],
            'isMentor' => $viewer->isMentor(),
            'categoryGroups' => $this->categoryGroups($viewer),
        ];
    }

    public function create(Request $request)
    {
        $viewer = auth()->user();

        if ($request->input('type') === WorkTask::TYPE_DAILY) {
            return view('admin.work-logs.daily-form', $this->dailyFormData($viewer, null));
        }

        return view('admin.work-logs.form', [
            'task' => null,
            'participants' => User::peserta()->inScope($viewer)->with(['division', 'mentor'])->orderByName()->get(),
            'isMentor' => $viewer->isMentor(),
        ]);
    }

    /**
     * Tugas harian dari mentor/admin (sama seperti dari peserta, ditambah pemilihan peserta):
     *  - dengan turunan : satu/lebih peserta; tanggal pengerjaan + jumlah output diisi per turunan di halaman detail.
     *  - pencatatan     : atas nama satu peserta, dengan tanggal mulai, tanggal selesai, jumlah output (langsung selesai).
     */
    private function storeDaily(Request $request, User $viewer)
    {
        $children = $request->boolean('has_children');

        $rules = [
            'title' => 'required|string|max:150',
            'description' => 'required|string|max:5000',
            'category' => 'required|string|max:40',
            'quantity_unit' => $children ? 'required|string|max:40' : 'nullable|string|max:40|required_with:quantity',
            'deadline' => ['nullable', 'date', 'after_or_equal:' . ($children ? 'today' : 'start_date')],
        ];
        // Peserta dipilih lewat kartu: dengan turunan boleh banyak, pencatatan tepat satu.
        $rules['participants'] = $children ? 'required|array|min:1' : 'required|array|size:1';
        $rules['participants.*'] = 'integer';
        if ($children) {
            $rules += [
                'target_quantity' => 'nullable|integer|min:1|max:1000000',
                'require_attachment' => 'required|in:0,1',
            ];
        } else {
            $rules += [
                'start_date' => 'required|date|before_or_equal:today',
                'end_date' => 'required|date|after_or_equal:start_date|before_or_equal:today',
                'quantity' => 'nullable|integer|min:0|max:1000000',
                'work_status' => 'required|in:' . implode(',', array_keys(WorkTask::WORK_STATUSES)),
            ];
        }
        $data = $request->validate($rules, [
            'category.required' => 'Pilih kategori.',
            'quantity_unit.required' => 'Satuan output wajib diisi untuk tugas dengan turunan.',
            'quantity_unit.required_with' => 'Satuan output wajib diisi bila jumlah diisi.',
            'target_quantity.min' => 'Target minimal 1.',
            'deadline.after_or_equal' => 'Tenggat tidak boleh sebelum tanggal mulai tugas.',
            'participants.required' => 'Pilih minimal satu peserta magang.',
            'participants.min' => 'Pilih minimal satu peserta magang.',
            'participants.size' => 'Pilih tepat satu peserta untuk pencatatan.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'start_date.before_or_equal' => 'Tanggal mulai tidak boleh melewati hari ini.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'end_date.before_or_equal' => 'Tanggal selesai tidak boleh melewati hari ini.',
            'work_status.required' => 'Status pengerjaan wajib dipilih.',
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['participants'])));
        $users = User::peserta()->inScope($viewer)->whereIn('users.id', $ids)->get();

        if ($users->count() !== count($ids)) {
            return back()->withInput()->withErrors(['participants' => 'Ada peserta yang tidak valid atau di luar divisi Anda.']);
        }

        // Kategori harus salah satu kategori divisi peserta yang dipilih.
        $allowed = [];
        foreach ($users->pluck('division_id')->unique() as $divId) {
            $allowed = array_merge($allowed, WorkCategory::namesFor($divId));
        }
        if (! in_array($data['category'], $allowed, true)) {
            return back()->withInput()->withErrors(['category' => 'Kategori tidak tersedia untuk divisi peserta yang dipilih.']);
        }

        $task = null;
        DB::transaction(function () use ($data, $viewer, $users, $children, $ids, &$task) {
            $hasQty = ! $children && isset($data['quantity']) && $data['quantity'] !== null;
            $unit = trim((string) ($data['quantity_unit'] ?? ''));
            $deadline = $data['deadline'] ?? null;
            $start = $children ? now()->toDateString() : $data['start_date'];

            $task = WorkTask::create([
                'type' => WorkTask::TYPE_DAILY,
                'code' => WorkTask::generateCode(WorkTask::TYPE_DAILY, WorkTask::divisionIdFor($viewer->id, null, $ids)),
                'title' => trim($data['title']),
                'description' => trim($data['description']),
                'category' => $data['category'],
                'has_children' => $children,
                'target_quantity' => $children && ! empty($data['target_quantity']) ? (int) $data['target_quantity'] : null,
                'quantity' => $hasQty ? (int) $data['quantity'] : null,
                'quantity_unit' => $unit !== '' ? $unit : null,
                'work_status' => $children ? null : $data['work_status'],
                'deadline' => $deadline,
                'worked_on' => $start,
                'start_date' => $start,
                'end_date' => $children ? $deadline : $data['end_date'],
                'require_attachment' => $children ? (bool) $data['require_attachment'] : false,
                'is_self' => false,
                'created_by' => $viewer->id,
            ]);

            foreach ($users as $u) {
                WorkTaskAssignee::create([
                    'work_task_id' => $task->id,
                    'user_id' => $u->id,
                    // Pencatatan oleh mentor langsung disetujui; tugas dengan turunan menunggu dikerjakan peserta.
                    'status' => $children ? WorkTaskAssignee::BELUM : WorkTaskAssignee::SUDAH,
                    'submitted_at' => $children ? null : now(),
                    'reviewed_at' => $children ? null : now(),
                    'reviewed_by' => $children ? null : $viewer->id,
                ]);
            }
        });

        if ($children) {
            flash()->success('Berhasil', 'Tugas dengan turunan dibuat untuk ' . count($ids) . ' peserta. Peserta menambahkan turunan dari halaman detail.');

            return redirect()->route('work-logs.show', $task);
        }

        flash()->success('Berhasil', 'Pekerjaan harian dicatat untuk ' . $users->first()->name . '.');

        return redirect()->route('work-logs.index', ['type' => WorkTask::TYPE_DAILY, 'month' => now()->format('Y-m')]);
    }

    /** Ubah tugas harian buatan mentor/admin. Status & lampiran peserta tidak direset; jenis tugas (turunan/pencatatan) tetap. */
    private function updateDaily(Request $request, WorkTask $task, User $viewer)
    {
        $children = (bool) $task->has_children;
        $rules = [
            'title' => 'required|string|max:150',
            'description' => 'required|string|max:5000',
            'category' => 'required|string|max:40',
            'quantity_unit' => $children ? 'required|string|max:40' : 'nullable|string|max:40|required_with:quantity',
            'deadline' => ['nullable', 'date', 'after_or_equal:' . ($children ? $task->workedDate()->toDateString() : 'start_date')],
        ];
        if ($children) {
            $rules += [
                'target_quantity' => 'nullable|integer|min:1|max:1000000',
                'require_attachment' => 'required|in:0,1',
            ];
        } else {
            $rules += [
                'start_date' => 'required|date|before_or_equal:today',
                'end_date' => 'required|date|after_or_equal:start_date|before_or_equal:today',
                'quantity' => 'nullable|integer|min:0|max:1000000',
                'work_status' => 'required|in:' . implode(',', array_keys(WorkTask::WORK_STATUSES)),
            ];
        }
        $data = $request->validate($rules, [
            'quantity_unit.required' => 'Satuan output wajib diisi untuk tugas dengan turunan.',
            'quantity_unit.required_with' => 'Satuan output wajib diisi bila jumlah diisi.',
            'target_quantity.min' => 'Target minimal 1.',
            'deadline.after_or_equal' => 'Tenggat tidak boleh sebelum tanggal mulai tugas.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'end_date.before_or_equal' => 'Tanggal selesai tidak boleh melewati hari ini.',
        ]);

        $divIds = $task->assignees()->with('user')->get()->pluck('user.division_id')->unique();
        $allowed = [$task->category];
        foreach ($divIds as $divId) {
            $allowed = array_merge($allowed, WorkCategory::namesFor($divId));
        }
        if (! in_array($data['category'], $allowed, true)) {
            return back()->withInput()->withErrors(['category' => 'Kategori tidak tersedia untuk divisi peserta tugas ini.']);
        }

        $changes = [];
        $fmt = function ($d) { return \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d M Y'); };
        $unit = trim((string) ($data['quantity_unit'] ?? ''));
        $deadline = $data['deadline'] ?? null;

        if ($task->title !== trim($data['title'])) { $changes[] = TaskHistory::change('Judul', $task->title, trim($data['title'])); }
        if ((string) $task->category !== $data['category']) { $changes[] = TaskHistory::change('Kategori', $task->category ?: '-', $data['category']); }
        if ((string) $task->quantity_unit !== $unit) { $changes[] = TaskHistory::change('Satuan output', $task->quantity_unit ?: '-', $unit !== '' ? $unit : '-'); }
        if (optional($task->deadline)->toDateString() !== $deadline) {
            $changes[] = TaskHistory::change('Tenggat', $task->deadline ? $fmt($task->deadline) : '-', $deadline ? $fmt($deadline) : '-');
        }

        $update = [
            'title' => trim($data['title']),
            'description' => trim($data['description']),
            'category' => $data['category'],
            'quantity_unit' => $unit !== '' ? $unit : null,
            'deadline' => $deadline,
        ];

        if ($children) {
            $newTarget = ! empty($data['target_quantity']) ? (int) $data['target_quantity'] : null;
            if ($task->target_quantity !== $newTarget) {
                $changes[] = TaskHistory::change('Target total', $task->target_quantity !== null ? (string) $task->target_quantity : '-', $newTarget !== null ? (string) $newTarget : '-');
            }
            $update += ['target_quantity' => $newTarget, 'end_date' => $deadline, 'require_attachment' => (bool) $data['require_attachment']];
        } else {
            $newStart = \Carbon\Carbon::parse($data['start_date'])->toDateString();
            $newEnd = \Carbon\Carbon::parse($data['end_date'])->toDateString();
            $oldEnd = optional($task->endDate())->toDateString();
            if ($task->workedDate()->toDateString() !== $newStart) { $changes[] = TaskHistory::change('Tanggal mulai', $fmt($task->workedDate()), $fmt($newStart)); }
            if ($oldEnd !== $newEnd) { $changes[] = TaskHistory::change('Tanggal selesai', $oldEnd ? $fmt($oldEnd) : '-', $fmt($newEnd)); }

            $hasQty = isset($data['quantity']) && $data['quantity'] !== null;
            $newQty = $hasQty ? (int) $data['quantity'] : null;
            if ($task->quantity !== $newQty) { $changes[] = TaskHistory::change('Jumlah output', $task->quantity !== null ? (string) $task->quantity : '-', $hasQty ? (string) $newQty : '-'); }
            if ((string) $task->work_status !== $data['work_status']) { $changes[] = TaskHistory::change('Status pengerjaan', $task->workStatusLabel() ?: '-', WorkTask::WORK_STATUSES[$data['work_status']]); }

            $update += [
                'worked_on' => $newStart, 'start_date' => $newStart, 'end_date' => $newEnd,
                'quantity' => $newQty, 'work_status' => $data['work_status'],
            ];
        }

        $task->update($update);
        if ($changes) {
            TaskHistory::record($task, $viewer, $changes);
        }

        flash()->success('Berhasil', 'Tugas harian diperbarui.');

        return redirect()->route('work-logs.show', $task);
    }

    public function store(Request $request)
    {
        if ($request->input('type') === WorkTask::TYPE_DAILY) {
            return $this->storeDaily($request, auth()->user());
        }

        $viewer = auth()->user();

        $data = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'required|string|max:5000',
            'deadline' => 'required|date|after_or_equal:today',
            'reference_files' => 'nullable|array|max:10',
            'reference_files.*' => 'file|max:10240|mimes:' . self::FILE_MIMES,
            'reference_links' => 'nullable|array|max:10',
            'reference_links.*' => 'nullable|url|max:500',
            'reference_link_labels' => 'nullable|array',
            'reference_link_labels.*' => 'nullable|string|max:120',
            'require_attachment' => 'required|in:0,1',
            'participants' => 'required|array|min:1',
            'participants.*' => 'integer',
        ], [
            'participants.required' => 'Pilih minimal satu peserta magang.',
            'participants.min' => 'Pilih minimal satu peserta magang.',
            'deadline.required' => 'Tenggat wajib diisi.',
            'deadline.date' => 'Tenggat tidak valid.',
            'deadline.after_or_equal' => 'Tenggat tidak boleh sebelum hari ini.',
        ] + $this->referenceMessages());

        $ids = array_values(array_unique(array_map('intval', $data['participants'])));
        $allowed = User::peserta()->inScope($viewer)->whereIn('users.id', $ids)->pluck('users.id')->all();

        if (count($allowed) !== count($ids)) {
            return back()->withInput()->withErrors([
                'participants' => 'Ada peserta yang tidak valid atau di luar divisi Anda.',
            ]);
        }

        $storedFiles = $this->storeReferenceFiles((array) $request->file('reference_files', []));
        $links = $this->pairLinks($data['reference_links'] ?? [], $data['reference_link_labels'] ?? []);

        DB::transaction(function () use ($data, $viewer, $storedFiles, $links, $allowed) {
            $task = WorkTask::create([
                'type' => WorkTask::TYPE_PROJECT,
                'code' => WorkTask::generateCode(WorkTask::TYPE_PROJECT, WorkTask::divisionIdFor($viewer->id, null, $allowed)),
                'title' => trim($data['title']),
                'description' => trim($data['description']),
                'deadline' => $data['deadline'],
                // Tanggal mulai = saat tugas dibuat; tanggal selesai = tenggat.
                'start_date' => now()->toDateString(),
                'end_date' => $data['deadline'],
                'require_attachment' => (bool) $data['require_attachment'],
                'is_self' => false,
                'created_by' => $viewer->id,
            ]);

            $this->addReferences($task, $storedFiles, $links);

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

    private function referenceMessages(): array
    {
        return [
            'reference_files.max' => 'Maksimal 10 file referensi.',
            'reference_files.*.max' => 'Ukuran tiap file maksimal 10 MB.',
            'reference_files.*.mimes' => 'Format file harus pdf, doc, xls, ppt, jpg, png, atau zip.',
            'reference_files.*.uploaded' => 'File gagal diunggah. Pastikan ukuran tidak melebihi batas server.',
            'reference_links.max' => 'Maksimal 10 tautan referensi.',
            'reference_links.*.url' => 'Tautan lampiran harus diawali http:// atau https://.',
        ];
    }

    public function show(WorkTask $task)
    {
        $viewer = auth()->user();
        abort_unless($task->isVisibleTo($viewer), 403);

        $task->load('creator', 'references.uploader', 'assignees.attachments.uploader');
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
            'canCancel' => $task->canBeCancelledBy($viewer),
            'canAddRef' => $task->canAddReferencesBy($viewer),
            'allCancelled' => $assignees->isNotEmpty() && $assignees->every(function ($a) {
                return $a->status === WorkTaskAssignee::BATAL;
            }),
        ]);
    }

    public function edit(WorkTask $task)
    {
        $viewer = auth()->user();
        abort_unless($task->isVisibleTo($viewer), 403);

        if (! $task->canBeEditedBy($viewer)) {
            flash()->error('Tidak bisa diedit', 'Tugas ini tidak bisa Anda edit.');

            return redirect()->route('work-logs.show', $task);
        }

        if ($task->isDaily() && ! $task->is_self) {
            return view('admin.work-logs.daily-form', $this->dailyFormData($viewer, $task));
        }

        return view('admin.work-logs.form', [
            'task' => $task,
            'participants' => User::peserta()->inScope($viewer)->with(['division', 'mentor'])->orderByName()->get(),
            'currentIds' => $task->assigneesFor($viewer)->pluck('user_id')->all(),
            'isMentor' => $viewer->isMentor(),
        ]);
    }

    public function update(Request $request, WorkTask $task)
    {
        $viewer = auth()->user();
        abort_unless($task->isVisibleTo($viewer), 403);

        if (! $task->canBeEditedBy($viewer)) {
            flash()->error('Tidak bisa diedit', 'Tugas ini tidak bisa Anda edit.');

            return redirect()->route('work-logs.show', $task);
        }

        if ($task->isDaily() && ! $task->is_self) {
            return $this->updateDaily($request, $task, $viewer);
        }

        // Tugas yang dicatat peserta: yang bisa diubah mentor hanya judul, deskripsi, dan rentang tanggal.
        if ($task->is_self) {
            return $this->updateSelfTask($request, $task, $viewer);
        }

        $data = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'required|string|max:5000',
            'deadline' => 'required|date|after_or_equal:' . $task->workedDate()->toDateString(),
            'require_attachment' => 'required|in:0,1',
            'participants' => 'required|array|min:1',
            'participants.*' => 'integer',
        ], [
            'participants.required' => 'Pilih minimal satu peserta magang.',
            'participants.min' => 'Pilih minimal satu peserta magang.',
            'deadline.required' => 'Tenggat wajib diisi.',
            'deadline.date' => 'Tenggat tidak valid.',
            'deadline.after_or_equal' => 'Tenggat tidak boleh sebelum tanggal mulai tugas.',
        ]);

        // Tenggat baru (bila diubah) tidak boleh di masa lalu.
        $oldDeadlineDate = optional($task->deadline)->toDateString();
        if ($data['deadline'] !== $oldDeadlineDate && \Carbon\Carbon::parse($data['deadline'])->lt(\Carbon\Carbon::today())) {
            return back()->withInput()->withErrors(['deadline' => 'Tenggat tidak boleh sebelum hari ini.']);
        }

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
            'deadline' => $data['deadline'],
            // Tanggal selesai selalu mengikuti tenggat; tanggal mulai tetap (sejak tugas dibuat).
            'end_date' => $data['deadline'],
            'require_attachment' => (bool) $data['require_attachment'],
        ];
        if (! $task->start_date) {
            $update['start_date'] = $task->workedDate()->toDateString();
        }

        // Catatan perubahan untuk popup Riwayat (dihitung sebelum data diubah).
        $fmtDate = function ($value) {
            return $value ? \Carbon\Carbon::parse($value)->locale('id')->translatedFormat('d M Y') : 'Tanpa tenggat';
        };
        $changes = [];
        if ((string) $task->title !== $update['title']) {
            $changes[] = \App\Support\TaskHistory::change('Judul', $task->title, $update['title']);
        }
        if (trim((string) $task->description) !== $update['description']) {
            $changes[] = \App\Support\TaskHistory::change('Deskripsi', $task->description, $update['description']);
        }
        $oldDeadline = optional($task->deadline)->toDateString();
        $newDeadline = \Carbon\Carbon::parse($update['deadline'])->toDateString();
        if ($oldDeadline !== $newDeadline) {
            $changes[] = \App\Support\TaskHistory::change('Tanggal selesai (tenggat)', $fmtDate($oldDeadline), $fmtDate($newDeadline));
        }
        if ((bool) $task->require_attachment !== $update['require_attachment']) {
            $changes[] = \App\Support\TaskHistory::change('Bukti pengerjaan', $task->require_attachment ? 'Wajib melampirkan' : 'Opsional', $update['require_attachment'] ? 'Wajib melampirkan' : 'Opsional');
        }
        if ($toAdd || $toRemove) {
            $addedNames = $toAdd ? User::whereIn('id', $toAdd)->pluck('name')->all() : [];
            $removedNames = $toRemove ? User::whereIn('id', $toRemove)->pluck('name')->all() : [];
            $changes[] = \App\Support\TaskHistory::note('Peserta', trim(
                ($addedNames ? 'Menambah: ' . implode(', ', $addedNames) . '. ' : '') . ($removedNames ? 'Mengeluarkan: ' . implode(', ', $removedNames) . '.' : '')
            ));
        }

        $removedPaths = [];
        $statusMoves = [];

        DB::transaction(function () use ($task, $update, $toAdd, $toRemove, &$removedPaths, &$statusMoves) {
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

            // Setiap pengeditan: status seluruh peserta kembali "belum" (lampiran tetap; yang batal tetap batal).
            $statusMoves = $task->resetAssigneeStatuses();
        });

        $task->unsetRelation('assignees');
        \App\Support\TaskHistory::record($task, $viewer, array_merge($changes, \App\Support\TaskHistory::statusChanges($task, $statusMoves)));

        foreach (array_unique($removedPaths) as $path) {
            $this->deleteFileIfUnused($path);
        }

        flash()->success('Berhasil', 'Tugas diperbarui. Status seluruh peserta dikembalikan menjadi Belum.');

        return redirect()->route('work-logs.show', $task);
    }

    /** Mentor/admin mengedit tugas yang dicatat peserta: judul, deskripsi, dan rentang tanggal. */
    private function updateSelfTask(Request $request, WorkTask $task, User $viewer)
    {
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'required|string|max:5000',
            'start_date' => 'required|date|before_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date|before_or_equal:today',
        ], [
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'start_date.date' => 'Tanggal mulai tidak valid.',
            'start_date.before_or_equal' => 'Tanggal mulai tidak boleh melewati hari ini.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.date' => 'Tanggal selesai tidak valid.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'end_date.before_or_equal' => 'Tanggal selesai tidak boleh melewati hari ini.',
        ]);

        $fmt = function ($d) { return \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d M Y'); };
        $update = [
            'title' => trim($data['title']),
            'description' => trim($data['description']),
            'worked_on' => \Carbon\Carbon::parse($data['start_date'])->toDateString(),
            'start_date' => \Carbon\Carbon::parse($data['start_date'])->toDateString(),
            'end_date' => \Carbon\Carbon::parse($data['end_date'])->toDateString(),
        ];

        // Catatan perubahan untuk popup Riwayat (dihitung sebelum data diubah).
        $changes = [];
        if ((string) $task->title !== $update['title']) {
            $changes[] = \App\Support\TaskHistory::change('Judul', $task->title, $update['title']);
        }
        if (trim((string) $task->description) !== $update['description']) {
            $changes[] = \App\Support\TaskHistory::change('Deskripsi', $task->description, $update['description']);
        }
        $oldStart = $task->workedDate()->toDateString();
        $oldEnd = optional($task->endDate())->toDateString();
        if ($oldStart !== $update['start_date']) {
            $changes[] = \App\Support\TaskHistory::change('Tanggal mulai', $fmt($oldStart), $fmt($update['start_date']));
        }
        if ($oldEnd !== $update['end_date']) {
            $changes[] = \App\Support\TaskHistory::change('Tanggal selesai', $oldEnd ? $fmt($oldEnd) : '-', $fmt($update['end_date']));
        }

        $statusMoves = [];

        DB::transaction(function () use ($task, $update, &$statusMoves) {
            $task->update($update);

            // Setiap pengeditan: status seluruh peserta kembali "belum" (lampiran tetap; yang batal tetap batal).
            $statusMoves = $task->resetAssigneeStatuses();
        });

        \App\Support\TaskHistory::record($task, $viewer, array_merge($changes, \App\Support\TaskHistory::statusChanges($task, $statusMoves)));

        flash()->success('Berhasil', 'Tugas peserta diperbarui. Status seluruh peserta dikembalikan menjadi Belum.');

        return redirect()->route('work-logs.show', $task);
    }

    /** Mentor/admin menambah referensi materi (file / tautan) langsung dari detail tugas, sama seperti peserta. */
    public function storeReferences(Request $request, WorkTask $task)
    {
        $viewer = auth()->user();
        abort_unless($task->canAddReferencesBy($viewer), 403);

        $count = $this->quickAddReferences($request, $task, $viewer);

        flash()->success('Berhasil', $count . ' referensi ditambahkan.');

        return redirect()->route('work-logs.show', $task);
    }

    /** Mentor/admin menghapus referensi apa pun pada tugas yang dapat dikelolanya (termasuk yang diunggah peserta). */
    public function destroyReference(WorkTask $task, WorkTaskReference $reference)
    {
        $viewer = auth()->user();
        abort_unless($task->canAddReferencesBy($viewer), 403);
        abort_unless((int) $reference->work_task_id === (int) $task->id, 404);

        $this->quickRemoveReference($task, $reference, $viewer);

        flash()->success('Berhasil', 'Referensi dihapus.');

        return redirect()->route('work-logs.show', $task);
    }

    /** Mentor/admin membatalkan tugas untuk seluruh peserta yang dikelolanya. Tugas tetap tampil di halaman mentor. */
    public function cancel(Request $request, WorkTask $task)
    {
        $viewer = auth()->user();
        abort_unless($task->isVisibleTo($viewer), 403);

        if (! $task->canBeCancelledBy($viewer)) {
            flash()->error('Tidak ada perubahan', 'Tugas ini sudah dibatalkan.');

            return redirect()->route('work-logs.show', $task);
        }

        $request->validate(['review_note' => 'nullable|string|max:500']);
        $note = $request->input('review_note');

        $moves = $task->cancelAssignees($task->assigneesFor($viewer), $viewer, $note);

        $changes = \App\Support\TaskHistory::statusChanges($task, $moves);
        if ($note) {
            $changes[] = \App\Support\TaskHistory::note('Catatan mentor', $note);
        }
        \App\Support\TaskHistory::record($task, $viewer, $changes);

        flash()->success('Berhasil', 'Tugas dibatalkan. Tugas tidak lagi tampil di halaman peserta, ubah status peserta untuk mengaktifkannya kembali.');

        return redirect()->route('work-logs.show', $task);
    }

    /** Mentor/admin mengubah status peserta: belum / menunggu / sudah / batal. */
    public function updateStatus(Request $request, WorkTask $task, WorkTaskAssignee $assignee)
    {
        $viewer = auth()->user();

        abort_unless((int) $assignee->work_task_id === (int) $task->id, 404);
        abort_unless($task->isVisibleTo($viewer), 403);
        abort_unless($task->assigneesFor($viewer)->contains('id', $assignee->id), 403);

        $data = $request->validate([
            'status' => 'required|in:belum,menunggu,sudah,batal',
            'review_note' => 'nullable|string|max:500',
        ]);

        if ($assignee->status === $data['status']) {
            flash()->error('Tidak ada perubahan', 'Status peserta sudah ' . WorkTaskAssignee::LABELS[$data['status']] . '.');

            return redirect()->route('work-logs.show', $task);
        }

        $note = $data['review_note'] ?? null;
        $oldStatus = $assignee->status;
        $assignee->loadMissing('user');

        if ($data['status'] === WorkTaskAssignee::BATAL) {
            // Dibatalkan: hilang dari halaman peserta, tetap tampil di halaman mentor. Lampiran tetap tersimpan.
            $task->cancelAssignees(collect([$assignee]), $viewer, $note);
        } elseif ($data['status'] === WorkTaskAssignee::SUDAH) {
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

        // Riwayat perubahan status (tampil di popup Riwayat).
        $changes = \App\Support\TaskHistory::statusChanges($task, [[
            'name' => optional($assignee->user)->name, 'old' => $oldStatus, 'new' => $data['status'],
        ]]);
        if ($note) {
            $changes[] = \App\Support\TaskHistory::note('Catatan mentor', $note);
        }
        \App\Support\TaskHistory::record($task, $viewer, $changes);

        flash()->success('Berhasil', 'Status ' . optional($assignee->user)->name . ' diubah menjadi ' . WorkTaskAssignee::LABELS[$data['status']] . '.'
            . ($oldStatus === WorkTaskAssignee::BATAL ? ' Tugas kembali tampil di halaman peserta.' : ''));

        return redirect()->route('work-logs.show', $task);
    }
}
