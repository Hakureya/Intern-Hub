<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membuang tabel latetimes dan overtimes.
 *
 * Kedua tabel ini sudah tidak ditulis aplikasi (penulisnya, lateTimeDevice() dan
 * overTimeDevice(), sudah dihapus) dan model Latetime/Overtime sudah dihapus.
 * Keterlambatan kini dibaca dari attendances.status = 0.
 *
 * PERINGATAN: data di kedua tabel hilang permanen. Backup dulu jika perlu.
 * down() hanya membuat ulang strukturnya (tanpa data).
 */
class DropLatetimesAndOvertimesTables extends Migration
{
    public function up()
    {
        Schema::dropIfExists('latetimes');
        Schema::dropIfExists('overtimes');
    }

    public function down()
    {
        Schema::create('latetimes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('emp_id');
            $table->time('duration');
            $table->date('latetime_date');
            $table->timestamps();

            $table->foreign('emp_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('overtimes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('emp_id');
            $table->time('duration');
            $table->date('overtime_date');
            $table->timestamps();

            $table->foreign('emp_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
}
