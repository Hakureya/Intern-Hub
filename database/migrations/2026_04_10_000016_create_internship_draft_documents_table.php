<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('internship_draft_documents');

        Schema::create('internship_draft_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('uploaded_by');
            $table->string('document_type', 32); // monthly|final
            $table->string('title');
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->timestamps();

            $table->index('document_type');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internship_draft_documents');
    }
};
