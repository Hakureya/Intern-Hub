<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesWorkTaskFiles;
use App\Models\User;
use App\Models\WorkTask;
use App\Models\WorkTaskAssignee;
use App\Models\WorkTaskAttachment;
use App\Models\WorkTaskPartnerRequest;
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

        $tasks = WorkTask::with(['assignees.user.division', 'assignees.user.mentor', 'creator'])
            ->visibleTo($me)
            ->inMonth($month)
            ->orderByRaw('COALESCE(work_tasks.deadline, work_tasks.worked_on, DATE(work_tasks.created_at)) desc')
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

        $received = WorkTaskPartnerRequest::with(['task', 'from.division'])
            ->where('to_user_id', $me->id)->latest()->get();
        $sent = WorkTaskPartnerRequest::with(['task', 'to.division'])
            ->where('from_user_id', $me->id)->latest()->get();

        return view('user.work-logs.index', [
            'rows' => $rows,
            'month' => $month,
            'status' => $status,
            'stats' => $stats,
            'received' => $received,
            'sent' => $sent,
            'pendingCount' => $received->where('status', WorkTaskPartnerRequest::PENDING)->count(),
        ]);
    }

    public function create()
    {
        return view('user.work-logs.create', [
            'task' => null,
            'partners' => $this->partnerCandidates(auth()->user()),
        ]);
    }

    /** "Dikerjakan pada": tidak boleh di masa depan dan tidak sebelum periode magang dimulai. */
    private function workedOnRules($user): array
    {
        $rules = ['required', 'date', 'before_or_equal:today'];

        if ($user->internship_start_date) {
            $start = \Carbon\Carbon::parse($user->internship_start_date);
            if ($start->lte(now())) {
                $rules[] = 'after_or_equal:' . $start->toDateString();
            }
        }

        return $rules;
    }

    private function workedOnMessages(): array
    {
        return [
            'worked_on.required' => 'Tanggal dikerjakan wajib diisi.',
            'worked_on.date' => 'Tanggal dikerjakan tidak valid.',
            'worked_on.before_or_equal' => 'Tanggal dikerjakan tidak boleh melewati hari ini.',
            'worked_on.after_or_equal' => 'Tanggal dikerjakan tidak boleh sebelum periode magang dimulai.',
        ];
    }

    public function store(Request $request)
    {
        $me = auth()->user();

        $data = $request->validate(array_merge([
            'title' => 'required|string|max:150',
            'description' => 'required|string|max:5000',
            'worked_on' => $this->workedOnRules($me),
            'partners' => 'nullable|array',
            'partners.*' => 'integer',
            'link_urls' => 'nullable|array|max:10',
            'link_urls.*' => 'nullable|url|max:500',
            'link_labels' => 'nullable|array',
            'link_labels.*' => 'nullable|string|max:120',
        ], $this->attachmentRules()), array_merge($this->attachmentMessages(), $this->workedOnMessages(), [
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
                'title' => trim($data['title']),
                'description' => trim($data['description']),
                'deadline' => null,
                'worked_on' => $data['worked_on'],
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

        $task->load('creator', 'references', 'assignees.user.division', 'assignees.user.mentor', 'assignees.attachments');
        $mine = $task->assignees->firstWhere('user_id', $me->id);

        return view('user.work-logs.show', [
            'task' => $task,
            'mine' => $mine,
            'canWork' => $mine && $mine->status !== WorkTaskAssignee::SUDAH,
            'people' => $task->assignees->where('user_id', '!=', $me->id)->map(function ($a) use ($me) {
                return WorkTask::cardPayload($a->user, $me);
            })->values()->all(),
            'canEdit' => $task->canBeEditedBy($me),
        ]);
    }

    public function edit(WorkTask $task)
    {
        $me = auth()->user();
        abort_unless($task->isVisibleTo($me), 403);

        if (! $task->canBeEditedBy($me)) {
            flash()->error('Tidak bisa diedit', 'Tugas hanya bisa diedit oleh pembuatnya dan sebelum tenggat.');

            return redirect()->route('user.work-logs.show', $task);
        }

        return view('user.work-logs.create', [
            'task' => $task,
            'partners' => collect(),
        ]);
    }

    public function update(Request $request, WorkTask $task)
    {
        $me = auth()->user();
        abort_unless($task->isVisibleTo($me), 403);

        if (! $task->canBeEditedBy($me)) {
            flash()->error('Tidak bisa diedit', 'Tugas hanya bisa diedit oleh pembuatnya dan sebelum tenggat.');

            return redirect()->route('user.work-logs.show', $task);
        }

        $rules = [
            'title' => 'required|string|max:150',
            'description' => 'required|string|max:5000',
        ];
        if ($task->is_self) {
            $rules['worked_on'] = $this->workedOnRules($me);
        }
        $data = $request->validate($rules, $this->workedOnMessages());

        // Catatan perubahan untuk riwayat + ruang chat (dihitung sebelum data diubah).
        $changes = [];
        if ((string) $task->title !== trim($data['title'])) {
            $changes[] = \App\Support\TaskHistory::change('Judul', $task->title, trim($data['title']));
        }
        if (trim((string) $task->description) !== trim($data['description'])) {
            $changes[] = \App\Support\TaskHistory::change('Deskripsi', $task->description, trim($data['description']));
        }
        if (isset($data['worked_on'])) {
            $oldWorked = $task->workedDate()->toDateString();
            $newWorked = \Carbon\Carbon::parse($data['worked_on'])->toDateString();
            if ($oldWorked !== $newWorked) {
                $fmt = function ($d) { return \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d M Y'); };
                $changes[] = \App\Support\TaskHistory::change('Dikerjakan pada', $fmt($oldWorked), $fmt($newWorked));
            }
        }

        $task->update([
            'title' => trim($data['title']),
            'description' => trim($data['description']),
        ] + (isset($data['worked_on']) ? ['worked_on' => $data['worked_on']] : []));

        \App\Support\TaskHistory::record($task, $me, $changes);

        // Setiap pengeditan: status kembali "belum". Lampiran tetap tersimpan.
        $task->resetAssigneeStatuses();

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
            ? $task->assignees()->where('status', WorkTaskAssignee::BELUM)->get()
            : collect([$mine]);

        foreach ($targets as $target) {
            $target->update([
                'status' => WorkTaskAssignee::MENUNGGU,
                'proof_note' => $request->input('proof_note') ?: null,
                'submitted_at' => now(),
                'review_note' => null,
            ]);
        }

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
            ? $task->assignees()->where('status', WorkTaskAssignee::MENUNGGU)->get()
            : collect([$mine]);

        foreach ($targets as $target) {
            $target->update(['status' => WorkTaskAssignee::BELUM, 'submitted_at' => null]);
        }

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
