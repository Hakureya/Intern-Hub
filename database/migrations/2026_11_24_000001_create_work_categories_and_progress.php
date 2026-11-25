<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DEFAULTS = [
        'input_data' => 'Input Data',
        'validasi' => 'Penyusunan & Validasi Data',
        'administrasi' => 'Administrasi',
        'lainnya' => 'Lainnya',
    ];

    public function up(): void
    {
        // Kategori tugas harian per divisi (diatur mentor divisi / admin).
        Schema::create('work_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('division_id')->index();
            $table->string('name', 40);
            $table->timestamps();
            $table->unique(['division_id', 'name']);
        });

        // Catatan progres bertahap pada satu tugas (garis waktu), opsional membawa jumlah untuk target.
        Schema::create('work_task_progress', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('work_task_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->date('entry_date');
            $table->unsignedInteger('quantity')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::table('work_tasks', function (Blueprint $table) {
            $table->unsignedInteger('target_quantity')->nullable(); // target jumlah dokumen (tugas harian bertarget)
        });

        // Kategori lama tersimpan sebagai kunci -> ubah menjadi label.
        foreach (self::DEFAULTS as $key => $label) {
            DB::table('work_tasks')->where('category', $key)->update(['category' => $label]);
        }

        // Tiap divisi yang sudah ada mulai dengan 4 kategori standar.
        $now = now();
        foreach (DB::table('divisions')->pluck('id') as $divisionId) {
            foreach (self::DEFAULTS as $label) {
                DB::table('work_categories')->insert(['division_id' => $divisionId, 'name' => $label, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('work_tasks', function (Blueprint $table) {
            $table->dropColumn('target_quantity');
        });
        Schema::dropIfExists('work_task_progress');
        Schema::dropIfExists('work_categories');
    }
};
