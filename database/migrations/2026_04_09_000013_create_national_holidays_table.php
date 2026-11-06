<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNationalHolidaysTable extends Migration
{
    public function up()
    {
        Schema::create('national_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_date')->unique();
            $table->string('name');
            $table->string('type', 2)->default('LH'); // LH / CB
            $table->timestamps();

            $table->index('holiday_date');
            $table->index('type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('national_holidays');
    }
}
