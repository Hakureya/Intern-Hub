<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExtendedProfileFieldsToEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('profile_photo_path')->nullable()->after('institution');
            $table->string('emergency_contact_name')->nullable()->after('profile_photo_path');
            $table->string('emergency_contact_phone', 25)->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_relation')->nullable()->after('emergency_contact_phone');
            $table->string('bank_name')->nullable()->after('emergency_contact_relation');
            $table->string('bank_account_number')->nullable()->after('bank_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'profile_photo_path',
                'emergency_contact_name',
                'emergency_contact_phone',
                'emergency_contact_relation',
                'bank_name',
                'bank_account_number',
            ]);
        });
    }
}

