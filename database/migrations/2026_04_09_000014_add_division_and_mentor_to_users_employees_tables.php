<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDivisionAndMentorToUsersEmployeesTables extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('division_id')->nullable()->after('institution');
            $table->unsignedBigInteger('mentor_id')->nullable()->after('division_id');

            $table->foreign('division_id')->references('id')->on('divisions')->onDelete('set null');
            $table->foreign('mentor_id')->references('id')->on('mentors')->onDelete('set null');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('division_id')->nullable()->after('institution');
            $table->unsignedBigInteger('mentor_id')->nullable()->after('division_id');

            $table->foreign('division_id')->references('id')->on('divisions')->onDelete('set null');
            $table->foreign('mentor_id')->references('id')->on('mentors')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropForeign(['mentor_id']);
            $table->dropColumn(['division_id', 'mentor_id']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropForeign(['mentor_id']);
            $table->dropColumn(['division_id', 'mentor_id']);
        });
    }
}
