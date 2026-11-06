<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan foreign key uploaded_by -> users.id (onDelete set null) pada
 * monthly_reports dan final_reports.
 *
 * uploaded_by bersifat nullable. Jika akun pengunggah dihapus, laporan tetap
 * disimpan dan kolom ini menjadi NULL (laporan milik peserta dikendalikan oleh
 * emp_id, bukan uploaded_by).
 *
 * Nilai uploaded_by yang menunjuk ke akun yang sudah tidak ada dikosongkan dulu,
 * karena MySQL menolak foreign key jika masih ada nilai tanpa induk.
 * Jalankan setelah 2026_10_28_000001_add_emp_id_foreign_to_report_tables.
 */
class AddUploadedByForeignToReportTables extends Migration
{
    private const TABLES = ['monthly_reports', 'final_reports'];

    public function up()
    {
        foreach (self::TABLES as $table) {
            DB::table($table)
                ->whereNotNull('uploaded_by')
                ->whereNotIn('uploaded_by', DB::table('users')->select('id'))
                ->update(['uploaded_by' => null]);

            Schema::table($table, function (Blueprint $t) {
                $t->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down()
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['uploaded_by']);
            });
        }
    }
}
