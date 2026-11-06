<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Menghapus tabel employees. Peserta kini langsung berupa akun (users) berperan "user".
 *
 * Kolom emp_id di semua tabel data kehadiran/laporan sebelumnya berisi employees.id;
 * migrasi ini mengubah isinya menjadi users.id (lewat employees.user_id) lalu
 * mengarahkan foreign key-nya ke users.
 *
 * Baris laporan yatim (emp_id tidak lagi ada di employees) dihapus agar tidak
 * salah terhubung ke akun lain setelah pemetaan ulang.
 */
class DropEmployeesTable extends Migration
{
    /** Tabel dengan foreign key emp_id -> employees.id. */
    private const WITH_FK = [
        'attendances', 'leaves', 'latetimes', 'overtimes',
        'absence_requests', 'attendance_draft_documents', 'schedule_employees',
    ];

    /** Tabel dengan emp_id tanpa foreign key. */
    private const WITHOUT_FK = ['final_reports', 'monthly_reports'];

    public function up()
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        // 0. Pastikan setiap data peserta punya akun.
        $roleId = DB::table('roles')->where('slug', 'user')->value('id');
        if (! $roleId) {
            $roleId = DB::table('roles')->insertGetId([
                'slug' => 'user', 'name' => 'Pengguna', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        DB::table('employees')->whereNull('user_id')->orderBy('id')->get(['id'])->each(function ($employee) use ($roleId) {
            $userId = DB::table('users')->insertGetId([
                'name' => 'Peserta ' . $employee->id,
                'email' => 'peserta-' . $employee->id . '@tanpa-akun.local',
                'password' => Hash::make(Str::random(40)),
                'is_active' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('role_users')->insert(['user_id' => $userId, 'role_id' => $roleId]);
            DB::table('employees')->where('id', $employee->id)->update(['user_id' => $userId]);
        });

        $tables = array_merge(self::WITH_FK, self::WITHOUT_FK);

        // 1. Buang baris yatim (emp_id tidak ada di employees).
        foreach ($tables as $table) {
            DB::table($table)->whereNotIn('emp_id', DB::table('employees')->select('id'))->delete();
        }

        // 2. Lepas foreign key ke employees.
        foreach (self::WITH_FK as $table) {
            try {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropForeign(['emp_id']);
                });
            } catch (QueryException $e) {
                // foreign key memang sudah tidak ada
            }
        }

        // 3. emp_id: employees.id -> users.id.
        foreach ($tables as $table) {
            DB::statement(
                "UPDATE {$table} SET emp_id = (SELECT e.user_id FROM employees e WHERE e.id = {$table}.emp_id)"
            );
        }

        // 4. Foreign key baru ke users.
        foreach (self::WITH_FK as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreign('emp_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // 5. Hapus tabelnya.
        Schema::dropIfExists('employees');
    }

    /**
     * Membuat ulang employees dengan id = id akun, sehingga isi emp_id tidak perlu diubah.
     * Kolom profil tidak dikembalikan (datanya tetap di users).
     */
    public function down()
    {
        if (! Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id')->nullable()->unique();
                $table->text('permissions')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        $tables = array_merge(self::WITH_FK, self::WITHOUT_FK);

        $userIds = DB::table('role_users')
            ->join('roles', 'roles.id', '=', 'role_users.role_id')
            ->where('roles.slug', 'user')
            ->pluck('role_users.user_id')
            ->all();

        foreach ($tables as $table) {
            $userIds = array_merge($userIds, DB::table($table)->distinct()->pluck('emp_id')->all());
        }

        foreach (array_unique($userIds) as $userId) {
            if (! DB::table('employees')->where('id', $userId)->exists()) {
                DB::table('employees')->insert([
                    'id' => $userId, 'user_id' => $userId, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        foreach (self::WITH_FK as $table) {
            try {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropForeign(['emp_id']);
                });
            } catch (QueryException $e) {
            }

            Schema::table($table, function (Blueprint $t) {
                $t->foreign('emp_id')->references('id')->on('employees')->onDelete('cascade');
            });
        }
    }
}
