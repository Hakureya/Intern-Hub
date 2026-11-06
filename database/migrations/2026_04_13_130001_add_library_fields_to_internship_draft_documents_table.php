<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internship_draft_documents', function (Blueprint $table) {
            $table->string('library_category', 50)->nullable()->after('document_type');
            $table->text('description')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('internship_draft_documents', function (Blueprint $table) {
            $table->dropColumn(['library_category', 'description']);
        });
    }
};
