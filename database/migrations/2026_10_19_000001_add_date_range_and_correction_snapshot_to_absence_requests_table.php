<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDateRangeAndCorrectionSnapshotToAbsenceRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('absence_requests', function (Blueprint $table) {
            // Izin/sakit kini berupa rentang tanggal: absence_date = mulai, absence_end_date = selesai.
            $table->date('absence_end_date')->nullable()->after('absence_date');

            // Foto jam kehadiran SEBELUM koreksi diterapkan (untuk ditampilkan di detail kalender).
            $table->time('original_time_in')->nullable()->after('correction_time_out');
            $table->time('original_time_out')->nullable()->after('original_time_in');

            // Penanda koreksi sudah diterapkan, agar foto "sebelum" tidak tertimpa saat disetujui ulang.
            $table->timestamp('correction_applied_at')->nullable()->after('original_time_out');
        });

        // Pengajuan lama selalu 1 hari: tanggal selesai = tanggal mulai.
        DB::table('absence_requests')
            ->whereNull('absence_end_date')
            ->update(['absence_end_date' => DB::raw('absence_date')]);

        // Koreksi yang sudah pernah disetujui: tandai sudah diterapkan (data "sebelum" tidak tersedia).
        DB::table('absence_requests')
            ->where('absence_type', 'lupa_absensi')
            ->where('status', 'approved')
            ->update(['correction_applied_at' => DB::raw('COALESCE(reviewed_at, updated_at)')]);
    }

    public function down()
    {
        // Satu per satu: SQLite tidak mendukung dropColumn banyak kolom dalam satu perintah.
        foreach (['absence_end_date', 'original_time_in', 'original_time_out', 'correction_applied_at'] as $column) {
            Schema::table('absence_requests', function (Blueprint $table) use ($column) {
                $table->dropColumn($column);
            });
        }
    }
}
