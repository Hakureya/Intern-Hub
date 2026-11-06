<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWorkTaskAttachmentsTable extends Migration
{
    public function up()
    {
        Schema::create('work_task_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('work_task_assignee_id');
            $table->string('type', 10)->default('file'); // file | link
            $table->string('name');
            $table->string('path')->nullable();
            $table->string('url', 500)->nullable();
            $table->timestamps();

            $table->foreign('work_task_assignee_id')->references('id')->on('work_task_assignees')->onDelete('cascade');
        });

        // Pindahkan bukti satu-file lama (kolom proof_path/proof_name) ke tabel lampiran.
        $rows = DB::table('work_task_assignees')->whereNotNull('proof_path')->get();
        foreach ($rows as $row) {
            DB::table('work_task_attachments')->insert([
                'work_task_assignee_id' => $row->id,
                'type' => 'file',
                'name' => $row->proof_name ?: basename($row->proof_path),
                'path' => $row->proof_path,
                'url' => null,
                'created_at' => $row->submitted_at ?: now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('work_task_attachments');
    }
}
