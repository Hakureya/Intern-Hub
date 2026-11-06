<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAbsenceRequestsTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('absence_requests');

        Schema::create('absence_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('emp_id');
            $table->date('absence_date');
            $table->text('reason');
            $table->string('document_name')->nullable();
            $table->string('document_path')->nullable();
            $table->unsignedBigInteger('document_size')->nullable();
            $table->string('document_mime')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('admin_note')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('emp_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['emp_id', 'absence_date']);
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::table('absence_requests', function (Blueprint $table) {
            $table->dropForeign(['emp_id']);
            $table->dropForeign(['reviewed_by']);
        });
        Schema::dropIfExists('absence_requests');
    }
}
