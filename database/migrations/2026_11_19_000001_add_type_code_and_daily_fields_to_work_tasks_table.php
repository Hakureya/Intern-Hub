<?php

use App\Models\WorkTask;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_tasks', function (Blueprint $table) {
            $table->string('type', 10)->default('project')->after('id');      // project | daily
            $table->string('code', 30)->nullable()->unique()->after('type');  // PRJ-TIK-0001 / HRN-TIK-0001
            $table->string('category', 40)->nullable();                       // harian: kategori aktivitas
            $table->unsignedInteger('quantity')->nullable();                  // harian: Total selesai
            $table->string('quantity_unit', 40)->nullable();                  // harian: Jenis satuan
            $table->string('work_status', 20)->nullable();                    // harian: done | progress | blocked
            $table->index('type');
        });

        // Beri kode ke tugas lama (semuanya tugas proyek), urut id.
        DB::table('work_tasks')->orderBy('id')->chunkById(200, function ($tasks) {
            foreach ($tasks as $task) {
                DB::table('work_tasks')->where('id', $task->id)->update([
                    'code' => WorkTask::generateCode(WorkTask::TYPE_PROJECT, WorkTask::divisionIdFor($task->created_by, $task->id)),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('work_tasks', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropUnique(['code']);
            $table->dropColumn(['type', 'code', 'category', 'quantity', 'quantity_unit', 'work_status']);
        });
    }
};
