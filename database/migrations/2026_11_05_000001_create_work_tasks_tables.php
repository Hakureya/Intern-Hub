<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkTasksTables extends Migration
{
    public function up()
    {
        Schema::create('work_tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->text('description');
            $table->date('deadline')->nullable();
            $table->boolean('require_attachment')->default(false);
            $table->string('reference_link', 500)->nullable();
            $table->string('reference_file_path')->nullable();
            $table->string('reference_file_name')->nullable();
            $table->boolean('is_self')->default(false); // true = dibuat peserta sendiri
            $table->unsignedInteger('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index('deadline');
        });

        Schema::create('work_task_assignees', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('work_task_id');
            $table->unsignedInteger('user_id');
            $table->string('status', 20)->default('belum'); // belum | menunggu | sudah
            $table->text('proof_note')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('proof_name')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->string('review_note', 500)->nullable();
            $table->timestamps();

            $table->foreign('work_task_id')->references('id')->on('work_tasks')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['work_task_id', 'user_id']);
        });

        Schema::create('work_task_partner_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('work_task_id');
            $table->unsignedInteger('from_user_id');
            $table->unsignedInteger('to_user_id');
            $table->string('status', 20)->default('pending'); // pending | accepted | declined
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->foreign('work_task_id')->references('id')->on('work_tasks')->onDelete('cascade');
            $table->foreign('from_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('to_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['work_task_id', 'to_user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('work_task_partner_requests');
        Schema::dropIfExists('work_task_assignees');
        Schema::dropIfExists('work_tasks');
    }
}
