<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesWorkTaskFiles;
use App\Models\User;
use App\Models\WorkTask;
use App\Models\WorkTaskAssignee;
use App\Models\WorkTaskAttachment;
use App\Models\WorkTaskPartnerRequest;
use App\Models\WorkTaskReference;
use App\Support\TaskHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Log pekerjaan sisi peserta magang. */
class UserWorkTaskController extends Controller
{
    use HandlesWorkTaskFiles;

    private const PROOF_MIMES = 'jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,zip';

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            abort_unless(auth()->user() && auth()->user()->hasRole('user'), 403);

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $me = auth()->user();
        $month = $this->monthParam($request);
        $status = (string) $request->input('status', '');
        $status = in_array($status, ['belum', 'menunggu', 'sudah'], true) ? $status : '';
        $search = trim((string) $request->input('q', ''));
        $type = WorkTask::typeParam($request->input('type'));

        $tasks = WorkTask::with(['assignees.user.division', 'assignees.user.mentor', 'creator'])
            ->visibleTo($me)
            ->inMonth($month)
            ->where('work_tasks.type', $type)
            ->when($search !== '', function ($q) use ($search) {
                $q->search($search);
            })
            ->orderByRaw('COALESCE(work_tasks.start_date, work_tasks.worked_on, DATE(work_tasks.created_at)) desc')
            ->orderByDesc('work_tasks.id')
            ->get();

        $rows = $tasks->map(function ($task) use ($me) {
            return [
                'task' => $task,
                'mine' => $task->assignees->firstWhere('user_id', $me->id),
                'people' => $task->assignees->where('user_id', '!=', $me->id)->map(function ($a) use ($me) {
                    return WorkTask::cardPayload($a->user, $me);
                })->values()->all(),
            ];
        });

        $typeCounts = [
            WorkTask::TYPE_PROJECT => WorkTask::visibleTo($me)->inMonth($month)->where('work_tasks.type', WorkTask::TYPE_PROJECT)->count(),
            WorkTask::TYPE_DAILY => WorkTask::visibleTo($me)->inMonth($month)->where('work_tasks.type', WorkTask::TYPE_DAILY)->count(),
        ];

        $stats = [
            'belum' => $rows->filter(function ($r) { return optional($r['mine'])->status === 'belum'; })->count(),
            'menunggu' => $rows->filter(function ($r) { return optional($r['mine'])->status === 'menunggu'; })->count(),
            'sudah' => $rows->filter(function ($r) { return optional($r['mine'])->status === 'sudah'; })->count(),
        ];

        if ($status !== '') {
            $rows = $rows->filter(function ($r) use ($status) {
                return optional($r['mine'])->status === $status;
            })->values();
        }

        // Kartu dikelompokkan per tanggal mulai (sama seperti halaman mentor), terbaru di atas.
        $groups = $rows->groupBy(function ($r) {
            return $r['task']->workedDate()->toDateString();
        });

        $received = WorkTaskPartnerRequest::with(['task', 'from.division'])
            ->where('to_user_id', $me->id)->latest()->get();
        $sent = WorkTaskPartnerRequest::with(['task', 'to.division'])
            ->where('from_user_id', $me->id)->latest()->get();

        return view('user.work-logs.index', [
            'rows' => $rows,
            'groups' => $groups,
            'month' => $month,
            'search' => $search,
            'status' => $status,
            'type' => $type,
            'typeCounts' => $typeCounts,
            'stats' => $stats,
            'received' => $received,
            'sent' => $sent,
            'pendingCount' => $received->where('status', WorkTaskPartnerRequest::PENDING)->count(),
        ]);
    }

    public function create(Request $request)
    {
        return view('user.work-logs.create', [
            'task' => null,
            'type' => WorkTask::typeParam($request->input('type')),
            'partners' => $this->partnerCandidates(auth()->user()),
        ]);
    }

    /**
     * Rentang tanggal tugas yang dicatat peserta:
     * tanggal mulai tidak boleh di masa depan dan tidak sebelum periode magang dimulai;
     * tanggal selesai tidak boleh sebelum tanggal mulai dan tidak di masa depan.
     */
    private function dateRules($user): array
    {
        $startRules = ['required', 'date', 'before_or_equal:today'];

        if ($user->internship_start_date) {
            $start = \Carbon\Carbon::parse($user->internship_start_date);
            if ($start->lte(now())) {
                $startRules[] = 'after_or_equal:' . $start->toDateString();
            }
        }

        return [
            'start_date' => $startRules,
            'end_date' => ['required', 'date', 'after_or_equal:start_date', 'before_or_equal:today'],
        ];
    }

    private function dateMessages(): array
    {
        return [
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'start_date.date' => 'Tanggal mulai tidak valid.',
            'start_date.before_or_equal' => 'Tanggal mulai tidak boleh melewati hari ini.',
            'start_date.after_or_equal' => 'Tanggal mulai tidak boleh sebelum periode magang dimulai.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.date' => 'Tanggal selesai tidak valid.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'end_date.before_or_equal' => 'Tanggal selesai tidak boleh melewati hari ini.',
        ];
    }

    /** Aturan & pesan isian khusus tugas harian. */
    private function dailyRules($user): array
    {
        $dateRules = ['required', 'date', 'before_or_equal:today'];
        if ($user->internship_start_date) {
            $start = \Carbon\Carbon::parse($user->internship_start_date);
            if ($start->lte(now())) {
                $dateRules[] = 'after_or_equal:' . $start->toDateString();
            }
        }

        return [
            'work_date' => $dateRules,
            'category' => 'nullable|in:' . implode(',', array_keys(WorkTask::CATEGORIES)),
            'quantity' => 'nullable|integer|min:0|max:1000000',
            'quantity_unit' => 'nullable|string|max:40|required_with:quantity',
            'work_status' => 'required|in:' . implode(',', array_keys(WorkTask::WORK_STATUSES)),
        ];
    }

    private function dailyMessages(): array
    {
        return [
            'work_date.required' => 'Tanggal pengerjaan wajib diisi.',
            'work_date.date' => 'Tanggal pengerjaan tidak valid.',
            'work_date.before_or_equal' => 'Tanggal pengerjaan tidak boleh melewati hari ini.',
            'work_date.after_or_equal' => 'Tanggal pengerjaan tidak boleh sebelum periode magang dimulai.',
            'category.in' => 'Kategori tidak valid.',
            'quantity.integer' => 'Jumlah output harus berupa angka bulat.',
            'quantity.min' => 'Jumlah output tidak boleh negatif.',
            'quantity_unit.required_with' => 'Satuan output wajib diisi bila jumlah diisi.',
            'work_status.required' => 'Status pengerjaan wajib dipilih.',
            'work_status.in' => 'Status pengerjaan tidak valid.',
        ];
    }

    private function storeDaily(Request $request)
    {
        $me = auth()->user();

        $data = $request->validate(array_merge([
            'title' => 'required|string|max:150',
            'description' => 'required|string|max:5000',
            'link_urls' => 'nullable|array|max:10',
            'link_urls.*' => 'nullable|url|max:500',
            'link_labels' => 'nullable|array',
            'link_labels.*' => 'nullable|string|max:120',
        ], $this->dailyRules($me), $this->attachmentRules()), array_merge($this->attachmentMessages(), $this->dailyMessages(), [
            'link_urls.max' => 'Maksimal 10 tautan.',
            'link_urls.*.url' => 'Tautan harus diawali http:// atau https://.',
        ]));

        $links = $this->pairLinks($data['link_urls'] ?? [], $data['link_labels'] ?? []);

        DB::transaction(function () use ($data, $me, $request, $links) {
            $task = WorkTask::create([
                'type' => WorkTask::TYPE_DAILY,
                'code' => WorkTask::generateCode(WorkTask::TYPE_DAILY, WorkTask::divisionIdFor($me->id)),
                'title' => trim($data['title']),
                'description' => trim($data['description']),
                'category' => $data['category'] ?? null,
                'quantity' => isset($data['quantity']) && $data['quantity'] !== null ? (int) $data['quantity'] : null,
                'quantity_unit' => isset($data['quantity']) && $data['quantity'] !== null ? trim($data['quantity_unit']) : null,
                'work_status' => $data['work_status'],
                'deadline' => null,
                'worked_on' => $data['work_date'],
                'start_date' => $data['work_date'],
                'end_date' => $data['work_date'],
                'require_attachment' => false,
                'is_self' => true,
                'created_by' => $me->id,
            ]);

            $assignee = WorkTaskAssignee::create([
                'work_task_id' => $task->id,
                'user_id' => $me->id,
                'status' => WorkTaskAssignee::MENUNGGU,
                'submitted_at' => now(),
            ]);

            $this->addAttachments($task, $assignee, (array) $request->file('files', []), null, null, $links);
        });

        flash()->success('Berhasil', 'Tugas harian dicatat dan menunggu persetujuan mentor.');

        return redirect()->route('user.work-logs.index', ['type' => WorkTask::TYPE_DAILY]);
    }

    public function store(Request $request)
    {
        if ($request->input('type') === WorkTask::TYPE_DAILY) {
            return $this->storeDaily($request);
        }

        $me = auth()->user();

        $data = $request->validate(array_merge([
            'title' => 'required|string|max:150',
            'description' => 'required|string|max:5000',
            'partners' => 'nullable|array',
            'partners.*' => 'integer',
            'link_urls' => 'nullable|array|max:10',
            'link_urls.*' => 'nullable|url|max:500',
            'link_labels' => 'nullable|array',
            'link_labels.*' => 'nullable|string|max:120',
        ], $this->dateRules($me), $this->attachmentRules()), array_merge($this->attachmentMessages(), $this->dateMessages(), [
            'link_urls.max' => 'Maksimal 10 tautan.',
            'link_urls.*.url' => 'Tautan harus diawali http:// atau https://.',
        ]));

        // Lampiran bukti bersifat opsional pada tugas mandiri.

        $partnerIds = array_values(array_unique(array_map('intval', $data['partners'] ?? [])));
        $allowed = $this->partnerCandidates($me)->pluck('id')->all();

        if (array_diff($partnerIds, $allowed)) {
            return back()->withInput()->withErrors(['partners' => 'Teman yang dipilih harus satu divisi dengan Anda.']);
        }

        $links = $this->pairLinks($data['link_urls'] ?? [], $data['link_labels'] ?? []);

        DB::transaction(function () use ($data, $me, $request, $partnerIds, $links) {
            $task = WorkTask::create([
                'type' => WorkTask::TYPE_PROJECT,
                'code' => WorkTask::generateCode(WorkTask::TYPE_PROJECT, WorkTask::divisionIdFor($me->id)),
                'title' => trim($data['title']),
                'description' => trim($data['description']),
                'deadline' => null,
                'worked_on' => $data['start_date'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'require_attachment' => false,
                'is_self' => true,
                'created_by' => $me->id,
            ]);

            $assignee = WorkTaskAssignee::create([
                'work_task_id' => $task->id,
                'user_id' => $me->id,
                'status' => WorkTaskAssignee::MENUNGGU,
                'submitted_at' => now(),
            ]);

            $this->addAttachments(
                $task,
                $assignee,
                (array) $request->file('files', []),
                $request->input('link_url') ?: null,
                $request->input('link_label') ?: null,
                $links
            );

            foreach ($partnerIds as $partnerId) {
                WorkTaskPartnerRequest::create([
                    'work_task_id' => $task->id,
                    'from_user_id' => $me->id,
                    'to_user_id' => $partnerId,
                    'status' => WorkTaskPartnerRequest::PENDING,
                ]);
            }
        });

        flash()->success('Berhasil', 'Tugas dicatat dan menunggu persetujuan mentor.'
            . ($partnerIds ? ' Permintaan dikirim ke teman.' : ''));

        return redirect()->route('user.work-logs.index');
    }

    public function show(WorkTask $task)
    {
        $me = auth()->user();
        abort_unless($task->isVisibleTo($me), 403);

        $task->load('creator', 'references.uploader', 'assignees.user.division', 'assignees.user.mentor', 'assignees.attachments.uploader');
        $mine = $task->assignees->firstWhere('user_id', $me->id);

        return view('user.work-logs.show', [
            'task' => $task,
            'mine' => $mine,
            'canWork' => $mine && $mine->status !== WorkTaskAssignee::SUDAH,
            'people' => $task->assignees->where('user_id', '!=', $me->id)->map(function ($a) use ($me) {
                return WorkTask::cardPayload($a->user, $me);
            })->values()->all(),
            'canEdit' => $task->canBeEditedBy($me),
            'canAddRef' => $task->canAddReferencesBy($me),
            'canCancel' => $task->canBeCancelledBy($me),
        ]);
    }

    public function edit(WorkTask $task)
    {
        $me = auth()->user();
        abort_unless($task->isVisibleTo($me), 403);

        if (! $task->canBeEditedBy($me)) {
            flash()->error('Tidak bisa diedit', 'Tugas hanya bisa diedit oleh pembuat atau rekan kolaborasinya, dan sebelum tenggat.');

            return redirect()->route('user.work-logs.show', $task);
        }

        return view('user.work-logs.create', [
            'task' => $task,
            'type' => $task->type,
            'partners' => collect(),
        ]);
    }

    public function update(Request $request, WorkTask $task)
    {
        $me = auth()->user();
        abort_unless($task->isVisibleTo($me), 403);

        if (! $task->canBeEditedBy($me)) {
            flash()->error('Tidak bisa diedit', 'Tugas hanya bisa diedit oleh pembuat atau rekan kolaborasinya, dan sebelum tenggat.');

            return redirect()->route('user.work-logs.show', $task);
        }

        $rules = [
            'title' => 'required|string|max:150',
            'description' => 'required|string|max:5000',
        ];
        $messages = $this->dateMessages();
        if ($task->isDaily()) {
            $rules += $this->dailyRules($me);
            $messages += $this->dailyMessages();
        } elseif ($task->is_self) {
            $rules += $this->dateRules($me);
        }
        $data = $request->validate($rules, $messages);

        // Catatan perubahan untuk popup Riwayat (dihitung sebelum data diubah).
        $changes = [];
        if ((string) $task->title !== trim($data['title'])) {
            $changes[] = TaskHistory::change('Judul', $task->title, trim($data['title']));
        }
        if (trim((string) $task->description) !== trim($data['description'])) {
            $changes[] = TaskHistory::change('Deskripsi', $task->description, trim($data['description']));
        }
        $fmt = function ($d) { return \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d M Y'); };
        $dateUpdate = [];
        if ($task->isDaily()) {
            $newDate = \Carbon\Carbon::parse($data['work_date'])->toDateString();
            $oldDate = $task->workedDate()->toDateString();
            if ($oldDate !== $newDate) {
                $changes[] = TaskHistory::change('Tanggal pengerjaan', $fmt($oldDate), $fmt($newDate));
            }
            $hasQty = isset($data['quantity']) && $data['quantity'] !== null;
            $newQty = $hasQty ? (int) $data['quantity'] : null;
            if ($task->quantity !== $newQty || (string) $task->quantity_unit !== ($hasQty ? trim($data['quantity_unit']) : '')) {
                $changes[] = TaskHistory::change('Jumlah output',
                    $task->quantity !== null ? $task->quantity . ' ' . $task->quantity_unit : '-',
                    $hasQty ? $newQty . ' ' . trim($data['quantity_unit']) : '-');
            }
            if ((string) $task->work_status !== $data['work_status']) {
                $changes[] = TaskHistory::change('Status pengerjaan', $task->workStatusLabel() ?: '-', WorkTask::WORK_STATUSES[$data['work_status']]);
            }
            if ((string) $task->category !== (string) ($data['category'] ?? '')) {
                $changes[] = TaskHistory::change('Kategori', $task->categoryLabel() ?: '-', WorkTask::CATEGORIES[$data['category'] ?? ''] ?? '-');
            }

            $dateUpdate = [
                'worked_on' => $newDate, 'start_date' => $newDate, 'end_date' => $newDate,
                'category' => $data['category'] ?? null,
                'quantity' => $newQty,
                'quantity_unit' => $hasQty ? trim($data['quantity_unit']) : null,
                'work_status' => $data['work_status'],
            ];
        } elseif (isset($data['start_date'], $data['end_date'])) {
            $oldStart = $task->workedDate()->toDateString();
            $oldEnd = optional($task->endDate())->toDateString();
            $newStart = \Carbon\Carbon::parse($data['start_date'])->toDateString();
            $newEnd = \Carbon\Carbon::parse($data['end_date'])->toDateString();

            if ($oldStart !== $newStart) {
                $changes[] = TaskHistory::change('Tanggal mulai', $fmt($oldStart), $fmt($newStart));
            }
            if ($oldEnd !== $newEnd) {
                $changes[] = TaskHistory::change('Tanggal selesai', $oldEnd ? $fmt($oldEnd) : '-', $fmt($newEnd));
            }

            $dateUpdate = ['worked_on' => $newStart, 'start_date' => $newStart, 'end_date' => $newEnd];
        }

        $task->update([
            'title' => trim($data['title']),
            'description' => trim($data['description']),
        ] + $dateUpdate);

        // Setiap pengeditan: status kembali "belum" (lampiran tetap tersimpan), dan perubahan statusnya ikut dicatat di riwayat.
        $statusMoves = $task->resetAssigneeStatuses();

        TaskHistory::record($task, $me, array_merge($changes, TaskHistory::statusChanges($task, $statusMoves)));

        flash()->success('Berhasil', 'Tugas diperbarui. Status kembali menjadi Belum, silakan ajukan tandai selesai lagi.');

        return redirect()->route('user.work-logs.show', $task);
    }

    /** Tambah lampiran pekerjaan: boleh banyak file dan/atau satu tautan. */
    public function storeAttachments(Request $request, WorkTask $task)
    {
        $mine = $this->workingAssignee($task);

        $request->validate($this->attachmentRules(), $this->attachmentMessages());

        if (! $request->hasFile('files') && ! $request->filled('link_url')) {
            return back()->withErrors(['files' => 'Pilih file atau isi tautan terlebih dahulu.']);
        }

        $count = $this->addAttachments(
            $task,
            $mine,
            (array) $request->file('files', []),
            $request->input('link_url') ?: null,
            $request->input('link_label') ?: null
        );

        flash()->success('Berhasil', $count . ' lampiran ditambahkan.');

        return redirect()->route('user.work-logs.show', $task);
    }

    public function destroyAttachment(WorkTask $task, WorkTaskAttachment $attachment)
    {
        $mine = $this->workingAssignee($task);

        abort_unless((int) $attachment->work_task_assignee_id === (int) $mine->id, 403);

        $attachment->load('assignee');
        $this->removeAttachment($task, $attachment);

        flash()->success('Berhasil', 'Lampiran dihapus.');

        return redirect()->route('user.work-logs.show', $task);
    }

    /** Peserta menambah referensi materi (file / tautan) pada detail tugas. Tidak mengubah status penyelesaian. */
    public function storeReferences(Request $request, WorkTask $task)
    {
        $me = auth()->user();
        abort_unless($task->canAddReferencesBy($me), 403);

        $count = $this->quickAddReferences($request, $task, $me);

        flash()->success('Berhasil', $count . ' referensi ditambahkan.');

        return redirect()->route('user.work-logs.show', $task);
    }

    /** Peserta hanya boleh menghapus referensi yang ia unggah sendiri. */
    public function destroyReference(WorkTask $task, WorkTaskReference $reference)
    {
        $me = auth()->user();
        abort_unless($task->canAddReferencesBy($me), 403);
        abort_unless((int) $reference->work_task_id === (int) $task->id, 404);
        abort_unless((int) $reference->uploaded_by === (int) $me->id, 403);

        $this->quickRemoveReference($task, $reference, $me);

        flash()->success('Berhasil', 'Referensi dihapus.');

        return redirect()->route('user.work-logs.show', $task);
    }

    /**
     * Batalkan tugas yang dicatat sendiri (pembuat atau rekan kolaborasi). Berlaku untuk seluruh pengerjanya:
     * tugas hilang dari halaman peserta tetapi tetap tampil di halaman mentor, yang bisa mengaktifkannya kembali.
     */
    public function cancel(WorkTask $task)
    {
        $me = auth()->user();
        abort_unless($task->isVisibleTo($me), 403);
        abort_unless($task->canBeCancelledBy($me), 403);

        $rows = $task->assignees()->with('user')->where('status', '!=', WorkTaskAssignee::SUDAH)->get();
        $moves = $task->cancelAssignees($rows);

        TaskHistory::record($task, $me, TaskHistory::statusChanges($task, $moves));

        flash()->success('Berhasil', 'Tugas dibatalkan. Tugas tidak lagi tampil di log pekerjaan Anda, namun tetap terlihat oleh mentor.');

        return redirect()->route('user.work-logs.index', ['month' => optional($task->endDate() ?: $task->workedDate())->format('Y-m')]);
    }

    /** Ajukan tandai selesai: status belum -> menunggu persetujuan. */
    public function submit(Request $request, WorkTask $task)
    {
        $me = auth()->user();
        $mine = $task->assignees()->where('user_id', $me->id)->firstOrFail();

        if ($mine->status !== WorkTaskAssignee::BELUM) {
            flash()->error('Gagal', 'Tugas ini sudah diajukan atau sudah selesai.');

            return redirect()->route('user.work-logs.show', $task);
        }

        $request->validate(['proof_note' => 'nullable|string|max:1000']);

        if ($task->require_attachment && $mine->attachments()->count() === 0) {
            return redirect()->route('user.work-logs.show', $task)
                ->withErrors(['attachments' => 'Tugas ini mewajibkan lampiran. Tambahkan minimal satu file atau tautan lebih dulu.']);
        }

        $targets = $task->is_self
            ? $task->assignees()->with('user')->where('status', WorkTaskAssignee::BELUM)->get()
            : collect([$mine->load('user')]);

        $moves = [];
        foreach ($targets as $target) {
            $moves[] = ['name' => optional($target->user)->name, 'old' => $target->status, 'new' => WorkTaskAssignee::MENUNGGU];
            $target->update([
                'status' => WorkTaskAssignee::MENUNGGU,
                'proof_note' => $request->input('proof_note') ?: null,
                'submitted_at' => now(),
                'review_note' => null,
            ]);
        }
        TaskHistory::record($task, $me, TaskHistory::statusChanges($task, $moves));

        flash()->success('Berhasil', 'Pengajuan selesai dikirim, menunggu persetujuan mentor.');

        return redirect()->route('user.work-logs.show', $task);
    }

    /** Tarik pengajuan: menunggu -> belum. Lampiran tetap tersimpan, bisa diajukan ulang. */
    public function withdraw(WorkTask $task)
    {
        $me = auth()->user();
        $mine = $task->assignees()->where('user_id', $me->id)->firstOrFail();

        if ($mine->status !== WorkTaskAssignee::MENUNGGU) {
            flash()->error('Gagal', 'Hanya pengajuan yang masih menunggu persetujuan yang bisa ditarik.');

            return redirect()->route('user.work-logs.show', $task);
        }

        // Tugas mandiri dikerjakan bersama: penarikan berlaku untuk seluruh kelompok yang masih menunggu.
        $targets = $task->is_self
            ? $task->assignees()->with('user')->where('status', WorkTaskAssignee::MENUNGGU)->get()
            : collect([$mine->load('user')]);

        $moves = [];
        foreach ($targets as $target) {
            $moves[] = ['name' => optional($target->user)->name, 'old' => $target->status, 'new' => WorkTaskAssignee::BELUM];
            $target->update(['status' => WorkTaskAssignee::BELUM, 'submitted_at' => null]);
        }
        TaskHistory::record($task, $me, TaskHistory::statusChanges($task, $moves));

        flash()->success('Berhasil', 'Pengajuan ditarik. Status kembali menjadi Belum.');

        return redirect()->route('user.work-logs.show', $task);
    }

    public function respond(Request $request, WorkTaskPartnerRequest $partnerRequest)
    {
        $me = auth()->user();

        abort_unless((int) $partnerRequest->to_user_id === (int) $me->id, 403);

        $data = $request->validate(['action' => 'required|in:accept,decline']);

        if ($partnerRequest->status !== WorkTaskPartnerRequest::PENDING) {
            flash()->error('Gagal', 'Permintaan ini sudah diproses.');

            return back();
        }

        if ($data['action'] === 'accept') {
            $owner = WorkTaskAssignee::where('work_task_id', $partnerRequest->work_task_id)
                ->where('user_id', $partnerRequest->from_user_id)->first();

            if ($owner && $owner->isCancelled()) {
                flash()->error('Gagal', 'Tugas ini sudah dibatalkan, permintaan tidak dapat diterima.');

                return back();
            }
        }

        DB::transaction(function () use ($partnerRequest, $data, $me) {
            if ($data['action'] === 'accept') {
                $creatorRow = WorkTaskAssignee::with('attachments')
                    ->where('work_task_id', $partnerRequest->work_task_id)
                    ->where('user_id', $partnerRequest->from_user_id)->first();

                $row = WorkTaskAssignee::firstOrCreate(
                    ['work_task_id' => $partnerRequest->work_task_id, 'user_id' => $me->id],
                    [
                        'status' => $creatorRow ? $creatorRow->status : WorkTaskAssignee::MENUNGGU,
                        'proof_note' => optional($creatorRow)->proof_note,
                        'submitted_at' => optional($creatorRow)->submitted_at,
                        'reviewed_at' => optional($creatorRow)->reviewed_at,
                        'reviewed_by' => optional($creatorRow)->reviewed_by,
                    ]
                );

                // Lampiran pembuat ikut muncul di akun teman (satu pekerjaan bersama).
                if ($row->wasRecentlyCreated && $creatorRow) {
                    $this->copyAttachments($creatorRow, $row);
                }
            }

            $partnerRequest->update([
                'status' => $data['action'] === 'accept' ? WorkTaskPartnerRequest::ACCEPTED : WorkTaskPartnerRequest::DECLINED,
                'responded_at' => now(),
            ]);
        });

        flash()->success('Berhasil', $data['action'] === 'accept'
            ? 'Permintaan diterima, tugas muncul di log pekerjaan Anda.'
            : 'Permintaan ditolak.');

        return redirect()->route('user.work-logs.index');
    }

    /** Baris penerima milik peserta ini; lampiran hanya bisa diubah selama belum "sudah". */
    private function workingAssignee(WorkTask $task): WorkTaskAssignee
    {
        $mine = $task->assignees()->where('user_id', auth()->id())->firstOrFail();

        abort_if($mine->status === WorkTaskAssignee::SUDAH, 403, 'Tugas sudah disetujui, lampiran tidak dapat diubah.');

        return $mine;
    }

    private function attachmentRules(): array
    {
        return [
            'files' => 'nullable|array|max:10',
            'files.*' => 'file|max:10240|mimes:' . self::PROOF_MIMES,
            'link_url' => 'nullable|url|max:500',
            'link_label' => 'nullable|string|max:120',
        ];
    }

    private function attachmentMessages(): array
    {
        return [
            'files.max' => 'Maksimal 10 file sekali unggah.',
            'files.*.max' => 'Ukuran tiap file maksimal 10 MB.',
            'files.*.mimes' => 'Format file harus jpg, png, webp, gif, pdf, doc, xls, ppt, atau zip.',
            'files.*.uploaded' => 'File gagal diunggah. Pastikan ukuran tidak melebihi batas server.',
            'link_url.url' => 'Tautan harus diawali http:// atau https://.',
        ];
    }

    /** Teman satu divisi (aktif), selain diri sendiri. */
    private function partnerCandidates(User $me)
    {
        if (! $me->division_id) {
            return collect();
        }

        return User::peserta()
            ->where('division_id', $me->division_id)
            ->where('users.id', '!=', $me->id)
            ->where(function ($q) {
                $q->whereNull('is_active')->orWhere('is_active', 1);
            })
            ->with(['division', 'mentor'])
            ->orderByName()
            ->get();
    }
}
