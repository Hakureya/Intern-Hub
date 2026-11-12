<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ruang chat per tugas. kind = text (pesan biasa) | edit (catatan perubahan tugas, otomatis).
        Schema::create('work_task_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('work_task_id');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('kind', 10)->default('text');
            $table->text('body')->nullable();
            $table->longText('changes')->nullable(); // JSON: daftar perubahan (kind = edit)
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();

            $table->index(['work_task_id', 'id']);
            $table->foreign('work_task_id')->references('id')->on('work_tasks')->onDelete('cascade');
        });

        Schema::create('work_task_message_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('message_id');
            $table->string('name');
            $table->string('path');
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();

            $table->foreign('message_id')->references('id')->on('work_task_messages')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_task_message_files');
        Schema::dropIfExists('work_task_messages');
    }
};
