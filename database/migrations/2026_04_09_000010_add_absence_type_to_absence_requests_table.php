<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAbsenceTypeToAbsenceRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('absence_requests', function (Blueprint $table) {
            $table->string('absence_type', 20)->default('izin')->after('absence_date');
        });
    }

    public function down()
    {
        Schema::table('absence_requests', function (Blueprint $table) {
            $table->dropColumn('absence_type');
        });
    }
}
