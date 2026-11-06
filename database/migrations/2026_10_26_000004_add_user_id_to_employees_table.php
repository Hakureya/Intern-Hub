<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menghubungkan employees dengan users lewat kolom user_id,
 * menggantikan pencocokan lewat email yang rapuh.
 *
 * - user_id boleh kosong (peserta yang dibuat admin tanpa akun login).
 * - unique: satu akun hanya untuk satu data peserta.
 * - Saat akun dihapus, data peserta (absensi, laporan) tetap ada (user_id jadi NULL).
 */
class AddUserIdToEmployeesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('employees', 'user_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->unsignedInteger('user_id')->nullable()->unique()->after('id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Isi user_id dari kecocokan email (tidak membedakan huruf besar/kecil).
        $linked = [];

        DB::table('employees')
            ->whereNull('user_id')
            ->whereNotNull('email')
            ->orderBy('id')
            ->get(['id', 'email'])
            ->each(function ($employee) use (&$linked) {
                $user = DB::table('users')
                    ->whereRaw('LOWER(email) = ?', [strtolower(trim($employee->email))])
                    ->first(['id']);

                if (! $user || isset($linked[$user->id])) {
                    return;
                }

                DB::table('employees')->where('id', $employee->id)->update(['user_id' => $user->id]);
                $linked[$user->id] = true;
            });
    }

    public function down()
    {
        if (Schema::hasColumn('employees', 'user_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropUnique(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }
}
