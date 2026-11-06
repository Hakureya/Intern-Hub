<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceDraftDocumentsTable extends Migration
{
    public function up()
    {
        Schema::create('attendance_draft_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('emp_id');
            $table->unsignedInteger('uploaded_by');
            $table->string('report_month', 7);
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->timestamps();

            $table->foreign('emp_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['emp_id', 'report_month']);
        });
    }

    public function down()
    {
        Schema::table('attendance_draft_documents', function (Blueprint $table) {
            $table->dropForeign(['emp_id']);
            $table->dropForeign(['uploaded_by']);
        });
        Schema::dropIfExists('attendance_draft_documents');
    }
}
