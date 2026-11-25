<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkTask;
use App\Models\WorkTaskAssignee;
use App\Http\Controllers\Concerns\HandlesWorkTaskFiles;
use Illuminate\Http\Request;

/**
 * Role viewer: hanya-baca log pekerjaan peserta di divisinya sendiri.
 * Tidak ada aksi ubah status, edit, batal, referensi, maupun chat.
 */
class ViewerWorkTaskController extends Controller
{
    use HandlesWorkTaskFiles;

    public function index(Request $request)
    {
        $viewer = auth()->user();
        $month = $this->monthParam($request);
        $search = trim((string) $request->input('q', ''));
        $status = (string) $request->input('status', '');
        $status = in_array($status, ['belum', 'menunggu', 'sudah', 'batal'], true) ? $status : '';
        $type = WorkTask::typeParam($request->input('type'));

        $participants = User::peserta()->inScope($viewer)->with('division')->orderByName()->get();
        $userId = (int) $request->input('user_id');
        if ($userId && ! $participants->contains('id', $userId)) {
            $userId = 0;
        }

        $tasks = WorkTask::with(['assignees.user.division', 'creator'])
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
            ->orderByRaw('COALESCE(work_tasks.start_date, work_tasks.worked_on, DATE(work_tasks.created_at)) desc')
            ->orderByDesc('work_tasks.created_at')
            ->orderByDesc('work_tasks.id')
            ->get();

        $rows = $tasks->map(function ($task) use ($viewer) {
            $assignees = $task->assigneesFor($viewer);

            return [
                'task' => $task,
                'assignees' => $assignees,
                'counts' => [
                    'belum' => $assignees->where('status', WorkTaskAssignee::BELUM)->count(),
                    'menunggu' => $assignees->where('status', WorkTaskAssignee::MENUNGGU)->count(),
                    'sudah' => $assignees->where('status', WorkTaskAssignee::SUDAH)->count(),
                    'batal' => $assignees->where('status', WorkTaskAssignee::BATAL)->count(),
                ],
            ];
        });

        $typeCounts = [
            WorkTask::TYPE_PROJECT => WorkTask::visibleTo($viewer)->inMonth($month)->where('work_tasks.type', WorkTask::TYPE_PROJECT)->count(),
            WorkTask::TYPE_DAILY => WorkTask::visibleTo($viewer)->inMonth($month)->where('work_tasks.type', WorkTask::TYPE_DAILY)->count(),
        ];

        $stats = [
            'semua' => $rows->count(),
            'sudah' => $rows->filter(function ($r) { return $r['counts']['sudah'] > 0; })->count(),
            'menunggu' => $rows->filter(function ($r) { return $r['counts']['menunggu'] > 0; })->count(),
            'belum' => $rows->filter(function ($r) { return $r['counts']['belum'] > 0; })->count(),
        ];

        if ($status !== '') {
            $rows = $rows->filter(function ($r) use ($status) {
                return $r['counts'][$status] > 0;
            })->values();
        }

        $groups = $rows->groupBy(function ($r) {
            return $r['task']->workedDate()->toDateString();
        });

        return view('viewer.work-logs.index', [
            'groups' => $groups,
            'month' => $month,
            'search' => $search,
            'status' => $status,
            'type' => $type,
            'typeCounts' => $typeCounts,
            'stats' => $stats,
            'userId' => $userId,
            'participants' => $participants,
            'viewer' => $viewer,
        ]);
    }

    public function show(WorkTask $task)
    {
        $viewer = auth()->user();
        abort_unless($task->isVisibleTo($viewer), 403);

        $task->load('creator', 'references.uploader', 'assignees.attachments.uploader');

        return view('viewer.work-logs.show', [
            'task' => $task,
            'assignees' => $task->assigneesFor($viewer),
            'viewer' => $viewer,
        ]);
    }
}
