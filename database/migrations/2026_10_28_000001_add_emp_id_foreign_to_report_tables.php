<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Menambahkan foreign key emp_id -> users.id (onDelete cascade) pada
 * monthly_reports dan final_reports.
 *
 * Migrasi 2026_10_26_000006_drop_employees_table memetakan ulang isi emp_id
 * kedua tabel ini menjadi users.id, tetapi tidak membuatkan foreign key-nya.
 *
 * Sebelum foreign key dibuat, baris yatim (emp_id tidak ada di users) dihapus
 * karena MySQL menolak foreign key jika masih ada baris yang tidak punya induk.
 * File PDF milik baris yatim itu ikut dihapus dari storage karena sudah tidak
 * bisa diakses siapa pun.
 */
class AddEmpIdForeignToReportTables extends Migration
{
    private const TABLES = ['monthly_reports', 'final_reports'];

    public function up()
    {
        $orphanFiles = [];

        foreach (self::TABLES as $table) {
            // 1. Hapus baris yatim, catat file-nya.
            $orphans = DB::table($table)
                ->whereNotIn('emp_id', DB::table('users')->select('id'));

            $orphanFiles = array_merge($orphanFiles, (clone $orphans)->pluck('file_path')->all());

            $orphans->delete();

            // 2. Pasang foreign key. Tipe emp_id (unsignedInteger) sama dengan users.id (increments).
            Schema::table($table, function (Blueprint $t) {
                $t->foreign('emp_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // 3. Hapus file yatim setelah database berhasil diubah.
        foreach (array_unique(array_filter($orphanFiles)) as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    public function down()
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['emp_id']);
            });
        }
    }
}
