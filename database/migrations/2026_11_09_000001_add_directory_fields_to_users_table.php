<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Apakah peserta ini boleh ditemukan lewat pencarian / Direktori Peserta.
            $table->boolean('is_discoverable')->default(true)->after('is_active');
            // Peta {kunci_isian: true/false} untuk isian yang tampil di kartu. Kosong = pakai bawaan.
            $table->text('card_visibility')->nullable()->after('is_discoverable');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_discoverable', 'card_visibility']);
        });
    }
};
