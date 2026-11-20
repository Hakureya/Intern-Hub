<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_tasks', function (Blueprint $table) {
            // Rentang pengerjaan tugas.
            // - Tugas dari mentor: tanggal mulai = tanggal tugas dibuat, tanggal selesai = tenggat.
            // - Tugas yang dicatat peserta: diisi sendiri oleh peserta.
            $table->date('start_date')->nullable()->after('deadline');
            $table->date('end_date')->nullable()->after('start_date');
            $table->index('end_date');
        });

        // Isi data lama.
        //   start_date: tanggal dikerjakan (tugas mandiri) atau tanggal dibuat.
        //   end_date  : tenggat (tugas mentor, tetap kosong bila dulu tanpa tenggat) atau tanggal dikerjakan (tugas mandiri).
        DB::table('work_tasks')->orderBy('id')->chunkById(200, function ($tasks) {
            foreach ($tasks as $task) {
                $created = $task->created_at ? substr((string) $task->created_at, 0, 10) : now()->toDateString();
                $start = $task->worked_on ?: $created;

                if ($task->is_self) {
                    $end = $task->worked_on ?: $created;
                } else {
                    $end = $task->deadline ?: null;
                }

                DB::table('work_tasks')->where('id', $task->id)->update([
                    'start_date' => $start,
                    'end_date' => $end,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('work_tasks', function (Blueprint $table) {
            $table->dropIndex(['end_date']);
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
