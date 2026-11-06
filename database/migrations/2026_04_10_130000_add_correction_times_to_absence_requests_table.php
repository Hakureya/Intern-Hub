<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCorrectionTimesToAbsenceRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('absence_requests', function (Blueprint $table) {
            $table->time('correction_time_in')->nullable()->after('absence_type');
            $table->time('correction_time_out')->nullable()->after('correction_time_in');
        });
    }

    public function down()
    {
        Schema::table('absence_requests', function (Blueprint $table) {
            $table->dropColumn(['correction_time_in', 'correction_time_out']);
        });
    }
}
