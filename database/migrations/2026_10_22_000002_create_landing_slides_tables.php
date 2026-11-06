<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_slides', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('image_position', 20)->default('center');
            $table->string('image_fit', 20)->default('cover');
            $table->string('bg_color', 9)->default('#bcdcee');
            $table->text('card')->nullable();   // JSON: pengaturan kartu
            $table->text('copy')->nullable();   // JSON: teks di samping slide
            $table->timestamps();
        });

        Schema::create('landing_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $now = now();

        $slides = [
            [
                'image_path' => 'images/magang/foto/kantor-plnsc.png',
                'image_alt' => 'Kantor PLN Suku Cadang',
                'card' => [
                    'label' => ['text' => 'Kantor PLN SC'],
                    'title' => ['text' => 'Menjadi pilihan utama solusi supply chain inovatif & berkelanjutan untuk ketenagalistrikan & utilitas di Indonesia.'],
                    'subtitle' => ['text' => 'Anak perusahaan Indonesia Power dan Nusantara Power'],
                ],
                'copy' => [
                    'eyebrow' => ['text' => 'Tentang Kami'],
                    'title' => ['text' => "Mendukung listrik\nIndonesia tetap *andal.*"],
                    'body' => ['text' => "PT PLN Suku Cadang (PLN SC) merupakan anak perusahaan Indonesia Power dan Nusantara Power yang berfokus pada penyediaan, pengelolaan, serta penyaluran suku cadang dan pembangkitan energi untuk mendukung keandalan sistem ketenagalistrikan di Indonesia. Didirikan untuk memastikan efisiensi pasokan material dan layanan pemeliharaan pembangkit, PLN SC mengelola rantai pasok secara terintegrasi, mulai dari pengadaan spare parts berkualitas tinggi, Reverse Engineering, hingga penanganan logistik.\n\nDengan dukungan teknologi terkini dan standar mutu yang ketat, perusahaan ini berperan penting dalam menjaga performa aset-aset pembangkit listrik agar tetap beroperasi secara optimal dan berkelanjutan."],
                    'items' => [
                        ['title' => 'Pengadaan suku cadang', 'text' => 'Penyediaan spare parts berkualitas tinggi untuk mendukung pemeliharaan pembangkit.'],
                        ['title' => 'Reverse Engineering', 'text' => 'Pengembangan suku cadang melalui rekayasa balik untuk menjaga efisiensi pasokan material.'],
                        ['title' => 'Logistik & rantai pasok terintegrasi', 'text' => 'Pengelolaan dan penyaluran material dengan dukungan teknologi dan standar mutu yang ketat.'],
                    ],
                ],
            ],

            [
                'image_path' => null,
                'image_alt' => null,
                'card' => [
                    'position' => 'middle-center',
                    'quote_mark' => true,
                    'title' => ['text' => 'Program magang ini membantu saya mengembangkan keterampilan teknis sekaligus memahami proses kerja di lingkungan profesional.', 'size' => 22, 'weight' => 600, 'italic' => true],
                    'subtitle' => ['text' => 'Hafidz Arif Budiono · Institut Teknologi PLN'],
                ],
                'copy' => [
                    'eyebrow' => ['text' => 'Cerita Peserta'],
                    'title' => ['text' => "Pengalaman magang\n*Hafidz.*"],
                    'body' => ['text' => "Selama magang di PLN Suku Cadang, Hafidz Arif Budiono dari Institut Teknologi PLN mengembangkan keterampilan teknis sekaligus mengenal langsung proses kerja di lingkungan profesional.\n\nBimbingan mentor dan tugas-tugas yang diberikan menjadi bekal agar ia lebih siap menghadapi tantangan di dunia kerja."],
                    'person' => ['enabled' => true, 'name' => 'Hafidz Arif Budiono', 'subtitle' => 'Peserta magang · Institut Teknologi PLN'],
                ],
            ],

            [
                'image_path' => 'images/magang/foto/kegiatan-rsti.jpeg',
                'image_alt' => 'Kegiatan magang: belajar membuat RSTI',
                'card' => [
                    'label' => ['text' => 'Kegiatan magang'],
                    'title' => ['text' => 'Belajar membuat RSTI'],
                    'subtitle' => ['text' => 'Rencana Strategis Teknologi Informasi'],
                ],
                'copy' => [
                    'eyebrow' => ['text' => 'Kegiatan Magang'],
                    'title' => ['text' => "Belajar membuat\n*RSTI.*"],
                    'body' => ['text' => "Mempelajari proses bisnis perusahaan, arsitektur teknologi informasi (IT), serta kondisi eksisting dan rencana pengembangan sistem informasi di PT Prima Layanan Niaga Suku Cadang.\n\nKegiatan ini juga mencakup diskusi bersama para mentor untuk memahami kebutuhan bisnis, mengidentifikasi permasalahan pada sistem yang berjalan, serta menyusun rekomendasi pengembangan teknologi informasi sebagai bagian dari penyusunan Rencana Strategis Teknologi Informasi (RSTI) perusahaan."],
                ],
            ],
        ];

        foreach ($slides as $i => $slide) {
            DB::table('landing_slides')->insert([
                'sort_order' => $i + 1,
                'is_active' => true,
                'image_path' => $slide['image_path'],
                'image_alt' => $slide['image_alt'],
                'image_position' => 'center',
                'image_fit' => 'cover',
                'bg_color' => '#bcdcee',
                'card' => json_encode($slide['card'], JSON_UNESCAPED_UNICODE),
                'copy' => json_encode($slide['copy'], JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('landing_settings')->insert([
            ['key' => 'slide_interval', 'value' => '6', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'slide_autoplay', 'value' => '1', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_settings');
        Schema::dropIfExists('landing_slides');
    }
};