<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menghapus employees.position. Kolom ini hanya cadangan dari nama divisi
 * (di profil peserta isinya sama dengan divisi, atau teks tetap "Pengguna").
 */
class DropPositionFromEmployeesTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('employees', 'position')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('position');
            });
        }
    }

    public function down()
    {
        if (! Schema::hasColumn('employees', 'position')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('position')->nullable();
            });
        }
    }
}
