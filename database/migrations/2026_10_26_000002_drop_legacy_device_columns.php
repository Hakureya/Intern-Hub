<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menghapus kolom sisa fitur mesin absensi (sidik jari/PIN) yang sudah tidak dipakai:
 * - attendances.uid, attendances.state
 * - leaves.uid, leaves.state
 * - users.pin_code, employees.pin_code
 */
class DropLegacyDeviceColumns extends Migration
{
    public function up()
    {
        foreach (['attendances', 'leaves'] as $tableName) {
            foreach (['uid', 'state'] as $column) {
                if (Schema::hasColumn($tableName, $column)) {
                    Schema::table($tableName, function (Blueprint $table) use ($column) {
                        $table->dropColumn($column);
                    });
                }
            }
        }

        foreach (['users', 'employees'] as $tableName) {
            if (Schema::hasColumn($tableName, 'pin_code')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('pin_code');
                });
            }
        }
    }

    public function down()
    {
        foreach (['attendances', 'leaves'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'uid')) {
                    $table->integer('uid')->unsigned()->default(0);
                }
                if (! Schema::hasColumn($tableName, 'state')) {
                    $table->boolean('state')->default(0);
                }
            });
        }

        foreach (['users', 'employees'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'pin_code')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('pin_code')->nullable();
                });
            }
        }
    }
}
