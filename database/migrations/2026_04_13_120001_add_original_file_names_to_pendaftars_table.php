<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOriginalFileNamesToPendaftarsTable extends Migration
{
    public function up()
    {
        Schema::table('pendaftars', function (Blueprint $table) {
            $table->string('cv_name')->nullable()->after('cv_path');
            $table->string('transkrip_name')->nullable()->after('transkrip_path');
            $table->string('surat_name')->nullable()->after('surat_path');
            $table->string('surat_permohonan_name')->nullable()->after('surat_permohonan_path');
            $table->json('dokumen_pendukung_name')->nullable()->after('dokumen_pendukung_path');
        });
    }

    public function down()
    {
        Schema::table('pendaftars', function (Blueprint $table) {
            $table->dropColumn([
                'cv_name',
                'transkrip_name',
                'surat_name',
                'surat_permohonan_name',
                'dokumen_pendukung_name',
            ]);
        });
    }
}
