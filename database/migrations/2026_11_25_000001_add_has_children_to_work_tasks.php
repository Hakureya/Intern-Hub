<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_tasks', function (Blueprint $table) {
            // true = tugas harian dengan turunan (tanggal pengerjaan + jumlah output diisi per turunan);
            // false = pencatatan (tanggal mulai, tanggal selesai, jumlah output diisi pada tugas itu sendiri).
            $table->boolean('has_children')->default(false);
        });

        // Tugas harian bertarget yang sudah ada (catatan progres harian) menjadi tugas dengan turunan.
        DB::table('work_tasks')->where('type', 'daily')->whereNotNull('target_quantity')->update(['has_children' => true]);
    }

    public function down(): void
    {
        Schema::table('work_tasks', function (Blueprint $table) {
            $table->dropColumn('has_children');
        });
    }
};
