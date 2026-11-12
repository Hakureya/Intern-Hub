<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lampiran referensi tugas dari mentor: boleh banyak file dan banyak tautan.
        Schema::create('work_task_references', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('work_task_id');
            $table->string('type', 10)->default('file'); // file | link
            $table->string('name');
            $table->string('path')->nullable();
            $table->string('url', 500)->nullable();
            $table->timestamps();

            $table->foreign('work_task_id')->references('id')->on('work_tasks')->onDelete('cascade');
        });

        // Pindahkan referensi satu-file / satu-tautan lama (kolom reference_*) ke tabel baru.
        foreach (DB::table('work_tasks')->whereNotNull('reference_file_path')->get() as $task) {
            DB::table('work_task_references')->insert([
                'work_task_id' => $task->id,
                'type' => 'file',
                'name' => $task->reference_file_name ?: basename($task->reference_file_path),
                'path' => $task->reference_file_path,
                'url' => null,
                'created_at' => $task->created_at ?: now(),
                'updated_at' => now(),
            ]);
        }
        foreach (DB::table('work_tasks')->whereNotNull('reference_link')->where('reference_link', '!=', '')->get() as $task) {
            DB::table('work_task_references')->insert([
                'work_task_id' => $task->id,
                'type' => 'link',
                'name' => parse_url($task->reference_link, PHP_URL_HOST) ?: $task->reference_link,
                'path' => null,
                'url' => $task->reference_link,
                'created_at' => $task->created_at ?: now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('work_task_references');
    }
};
