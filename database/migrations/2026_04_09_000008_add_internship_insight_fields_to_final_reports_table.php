<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInternshipInsightFieldsToFinalReportsTable extends Migration
{
    public function up()
    {
        Schema::table('final_reports', function (Blueprint $table) {
            $table->text('what_learned')->nullable()->after('file_size');
            $table->text('challenges')->nullable()->after('what_learned');
            $table->text('breakthrough_solution')->nullable()->after('challenges');
            $table->text('suggestions_for_company')->nullable()->after('breakthrough_solution');
        });
    }

    public function down()
    {
        Schema::table('final_reports', function (Blueprint $table) {
            $table->dropColumn([
                'what_learned',
                'challenges',
                'breakthrough_solution',
                'suggestions_for_company',
            ]);
        });
    }
}
