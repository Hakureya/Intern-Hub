<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_task_attachments', function (Blueprint $table) {
            $table->unsignedBigInteger('uploaded_by')->nullable()->after('url')->index();
        });
        Schema::table('work_task_references', function (Blueprint $table) {
            $table->unsignedBigInteger('uploaded_by')->nullable()->after('url')->index();
        });

        // Lampiran hasil pengerjaan lama: pengunggah = pemilik baris peserta. Salinan yang identik (tugas mandiri
        // bersama teman) dianggap diunggah oleh pemilik lampiran pertama (aslinya), bukan oleh teman yang menerima salinan.
        $seen = [];
        $rows = DB::table('work_task_attachments as a')
            ->join('work_task_assignees as s', 's.id', '=', 'a.work_task_assignee_id')
            ->select('a.id', 'a.type', 'a.path', 'a.url', 's.user_id', 's.work_task_id')
            ->orderBy('a.id')
            ->get();

        foreach ($rows as $row) {
            $key = $row->work_task_id . '|' . $row->type . '|' . ($row->type === 'link' ? $row->url : $row->path);
            if (! isset($seen[$key])) {
                $seen[$key] = $row->user_id;
            }
            DB::table('work_task_attachments')->where('id', $row->id)->update(['uploaded_by' => $seen[$key]]);
        }

        // Lampiran referensi lama: pengunggah = pembuat tugas (mentor).
        foreach (DB::table('work_tasks')->select('id', 'created_by')->get() as $task) {
            DB::table('work_task_references')->where('work_task_id', $task->id)->update(['uploaded_by' => $task->created_by]);
        }
    }

    public function down(): void
    {
        Schema::table('work_task_attachments', function (Blueprint $table) {
            $table->dropColumn('uploaded_by');
        });
        Schema::table('work_task_references', function (Blueprint $table) {
            $table->dropColumn('uploaded_by');
        });
    }
};
