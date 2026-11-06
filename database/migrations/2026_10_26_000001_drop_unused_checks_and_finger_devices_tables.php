<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menghapus tabel sisa fitur mesin absensi (sidik jari/PIN) yang sudah tidak dipakai:
 * - checks         (hanya ditulis oleh ApiController yang tidak punya rute)
 * - finger_devices (hanya dipakai BiometricDeviceController yang tidak punya rute)
 */
class DropUnusedChecksAndFingerDevicesTables extends Migration
{
    public function up()
    {
        Schema::dropIfExists('checks');
        Schema::dropIfExists('finger_devices');
    }

    public function down()
    {
        if (! Schema::hasTable('checks')) {
            Schema::create('checks', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('emp_id')->unsigned();
                $table->dateTime('attendance_time');
                $table->dateTime('leave_time')->nullable();
                $table->foreign('emp_id')->references('id')->on('employees')->onDelete('cascade');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('finger_devices')) {
            Schema::create('finger_devices', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->ipAddress('ip');
                $table->string('serialNumber')->unique();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }
}
