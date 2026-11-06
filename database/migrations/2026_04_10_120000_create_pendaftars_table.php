<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePendaftarsTable extends Migration
{
    public function up()
    {
        Schema::create('pendaftars', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_magang')->nullable();
            $table->string('tipe_mahasiswa')->nullable();
            $table->string('nama');
            $table->string('email')->unique();
            $table->string('no_hp');
            $table->text('alamat')->nullable();
            $table->string('asal_kampus');
            $table->string('jurusan');
            $table->decimal('ipk', 4, 2)->nullable();
            $table->integer('semester')->nullable();
            $table->string('periode')->nullable();
            $table->enum('status', ['Menunggu', 'Wawancara', 'Diterima', 'Ditolak'])->default('Menunggu');
            $table->text('pesan')->nullable();
            $table->dateTime('wawancara_waktu')->nullable();
            $table->string('wawancara_lokasi')->nullable();
            $table->string('cv_path')->nullable();
            $table->string('transkrip_path')->nullable();
            $table->string('surat_path')->nullable();
            $table->string('surat_permohonan_path')->nullable();
            $table->json('dokumen_pendukung_path')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pendaftars');
    }
}
