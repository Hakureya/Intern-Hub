<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Data profil peserta kini disimpan hanya di tabel users.
 *
 * 1. users.major ditambahkan (jurusan sebelumnya hanya ada di employees).
 * 2. Peserta tanpa akun (user_id kosong) dibuatkan akun NONAKTIF berpassword acak,
 *    supaya datanya tidak hilang. Admin bisa mengaktifkannya dan mengatur password di Manajemen Akun.
 * 3. Data profil di employees disalin ke users bila isi di users masih kosong.
 * 4. Kolom profil di employees dihapus.
 */
class ConsolidateEmployeeProfileIntoUsers extends Migration
{
    /** Kolom profil yang ada di users dan employees. */
    private const SHARED = [
        'phone_number', 'address', 'birth_date', 'institution',
        'division_id', 'mentor_id', 'internship_start_date', 'internship_end_date',
        'profile_photo_path',
        'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
        'bank_name', 'bank_account_number',
    ];

    public function up()
    {
        // 1. Jurusan pindah ke users.
        if (! Schema::hasColumn('users', 'major')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('major')->nullable()->after('institution');
            });
        }

        // 2. Peserta tanpa akun -> akun nonaktif.
        $roleId = DB::table('roles')->where('slug', 'user')->value('id');
        if (! $roleId) {
            $roleId = DB::table('roles')->insertGetId([
                'slug' => 'user',
                'name' => 'Pengguna',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('employees')->whereNull('user_id')->orderBy('id')->get()->each(function ($employee) use ($roleId) {
            $email = $employee->email ? trim($employee->email) : null;

            if (! $email || DB::table('users')->whereRaw('LOWER(email) = ?', [strtolower($email)])->exists()) {
                $email = 'peserta-' . $employee->id . '@tanpa-akun.local';
            }

            $data = [
                'name' => $employee->name ?: 'Peserta ' . $employee->id,
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'is_active' => 0,
                'major' => $employee->major,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            foreach (self::SHARED as $column) {
                $data[$column] = $employee->{$column};
            }

            $userId = DB::table('users')->insertGetId($data);
            DB::table('role_users')->insert(['user_id' => $userId, 'role_id' => $roleId]);
            DB::table('employees')->where('id', $employee->id)->update(['user_id' => $userId]);
        });

        // 3. Isi data users yang masih kosong dari employees.
        DB::table('employees')->whereNotNull('user_id')->orderBy('id')->get()->each(function ($employee) {
            $user = DB::table('users')->where('id', $employee->user_id)->first();
            if (! $user) {
                return;
            }

            $updates = [];
            foreach (array_merge(self::SHARED, ['major']) as $column) {
                $userValue = $user->{$column} ?? null;
                $employeeValue = $employee->{$column} ?? null;

                if (($userValue === null || $userValue === '') && $employeeValue !== null && $employeeValue !== '') {
                    $updates[$column] = $employeeValue;
                }
            }

            if ($updates) {
                DB::table('users')->where('id', $user->id)->update($updates);
            }
        });

        // 4. Hapus kolom profil di employees.
        if (Schema::hasColumn('employees', 'division_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropForeign(['division_id']);
            });
        }
        if (Schema::hasColumn('employees', 'mentor_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropForeign(['mentor_id']);
            });
        }

        $drop = array_values(array_filter(
            array_merge(self::SHARED, ['name', 'email', 'major']),
            fn ($column) => Schema::hasColumn('employees', $column)
        ));

        if ($drop) {
            Schema::table('employees', function (Blueprint $table) use ($drop) {
                $table->dropColumn($drop);
            });
        }
    }

    /**
     * Mengembalikan kolom di employees dan mengisinya dari users.
     * Index unik email dan foreign key divisi/mentor tidak dibuat ulang.
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'name')) {
                $table->string('name')->nullable();
            }
            if (! Schema::hasColumn('employees', 'email')) {
                $table->string('email')->nullable();
            }
            if (! Schema::hasColumn('employees', 'major')) {
                $table->string('major')->nullable();
            }
            foreach (self::SHARED as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    continue;
                }
                if (in_array($column, ['division_id', 'mentor_id'], true)) {
                    $table->unsignedBigInteger($column)->nullable();
                } elseif (in_array($column, ['birth_date', 'internship_start_date', 'internship_end_date'], true)) {
                    $table->date($column)->nullable();
                } elseif ($column === 'address') {
                    $table->text($column)->nullable();
                } else {
                    $table->string($column)->nullable();
                }
            }
        });

        DB::table('employees')->whereNotNull('user_id')->orderBy('id')->get()->each(function ($employee) {
            $user = DB::table('users')->where('id', $employee->user_id)->first();
            if (! $user) {
                return;
            }

            $values = ['name' => $user->name, 'email' => $user->email, 'major' => $user->major ?? null];
            foreach (self::SHARED as $column) {
                $values[$column] = $user->{$column} ?? null;
            }

            DB::table('employees')->where('id', $employee->id)->update($values);
        });
    }
}
