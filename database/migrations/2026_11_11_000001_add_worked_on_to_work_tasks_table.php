<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_tasks', function (Blueprint $table) {
            // Tanggal pekerjaan sebenarnya dikerjakan (untuk tugas mandiri yang dicatat belakangan). Kosong = pakai tanggal dibuat.
            $table->date('worked_on')->nullable()->after('deadline');
        });
    }

    public function down(): void
    {
        Schema::table('work_tasks', function (Blueprint $table) {
            $table->dropColumn('worked_on');
        });
    }
};
