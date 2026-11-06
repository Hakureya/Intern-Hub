<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mengganti nama kolom emp_id menjadi user_id (berisi users.id) di semua tabel
 * kehadiran dan laporan, serta tabel pivot schedule_employees menjadi schedule_user.
 *
 * Untuk tiap tabel: foreign key lama dilepas, kolom diganti nama (isi data tetap),
 * nama index yang memuat "emp_id" ikut diganti, lalu foreign key dipasang lagi ke
 * users.id dengan onDelete cascade.
 *
 * Memakai ALTER TABLE ... CHANGE agar tidak membutuhkan doctrine/dbal. Semua kolom
 * emp_id bertipe INT UNSIGNED NOT NULL, sama dengan users.id.
 *
 * Aplikasi tidak dapat dipakai antara migrasi ini dan pemasangan kode baru.
 * Jalankan bersamaan dengan deploy, dan backup database lebih dulu.
 */
class RenameEmpIdToUserId extends Migration
{
    /** [nama tabel lama => nama tabel baru] */
    private const TABLES = [
        'attendances'                => 'attendances',
        'leaves'                     => 'leaves',
        'absence_requests'           => 'absence_requests',
        'attendance_draft_documents' => 'attendance_draft_documents',
        'final_reports'              => 'final_reports',
        'monthly_reports'            => 'monthly_reports',
        'schedule_employees'         => 'schedule_user',
    ];

    public function up()
    {
        foreach (self::TABLES as $old => $new) {
            $this->convert($old, $new, 'emp_id', 'user_id');
        }
    }

    public function down()
    {
        foreach (self::TABLES as $old => $new) {
            $this->convert($new, $old, 'user_id', 'emp_id');
        }
    }

    private function convert(string $fromTable, string $toTable, string $fromCol, string $toCol): void
    {
        if (! Schema::hasTable($fromTable) || ! Schema::hasColumn($fromTable, $fromCol)) {
            return;
        }

        // 1. Lepas foreign key lama (nama bawaan Laravel: tabel_kolom_foreign).
        $fk = "{$fromTable}_{$fromCol}_foreign";
        if ($this->foreignKeyExists($fromTable, $fk)) {
            DB::statement("ALTER TABLE `{$fromTable}` DROP FOREIGN KEY `{$fk}`");
        }

        // 2. Ganti nama kolom; isi data tidak berubah.
        DB::statement("ALTER TABLE `{$fromTable}` CHANGE `{$fromCol}` `{$toCol}` INT UNSIGNED NOT NULL");

        // 3. Ganti nama tabel (khusus pivot jadwal).
        if ($fromTable !== $toTable) {
            Schema::rename($fromTable, $toTable);
        }

        // 4. Ganti nama index yang memuat nama kolom lama.
        foreach ($this->indexNames($toTable) as $name) {
            if (strpos($name, $fromCol) === false) {
                continue;
            }
            $newName = str_replace([$fromTable, $fromCol], [$toTable, $toCol], $name);
            if ($newName !== $name) {
                DB::statement("ALTER TABLE `{$toTable}` RENAME INDEX `{$name}` TO `{$newName}`");
            }
        }

        // 5. Pasang foreign key ke users.id.
        Schema::table($toTable, function (Blueprint $table) use ($toCol) {
            $table->foreign($toCol)->references('id')->on('users')->onDelete('cascade');
        });
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $name)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }

    private function indexNames(string $table): array
    {
        $names = [];
        foreach (DB::select("SHOW INDEX FROM `{$table}`") as $row) {
            $names[$row->Key_name] = true;
        }
        unset($names['PRIMARY']);

        return array_keys($names);
    }
}
