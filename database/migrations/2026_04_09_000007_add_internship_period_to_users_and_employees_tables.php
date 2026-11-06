<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInternshipPeriodToUsersAndEmployeesTables extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('internship_start_date')->nullable()->after('institution');
            $table->date('internship_end_date')->nullable()->after('internship_start_date');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->date('internship_start_date')->nullable()->after('institution');
            $table->date('internship_end_date')->nullable()->after('internship_start_date');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['internship_start_date', 'internship_end_date']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['internship_start_date', 'internship_end_date']);
        });
    }
}
