<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Division;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // 1. Dapatkan atau Buat Role Admin, User, & Super Admin
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Administrator']
        );

        Role::firstOrCreate(
            ['slug' => 'user'],
            ['name' => 'User']
        );

        $superRole = Role::firstOrCreate(
            ['slug' => 'super_admin'],
            ['name' => 'Super Administrator']
        );

        $mentorRole = Role::firstOrCreate(
            ['slug' => 'mentor'],
            ['name' => 'Mentor']
        );

        // 2. Akun Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@plnsc.co.id'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('superadmin@plnsc.co.id'),
            ]
        );

        $superAdmin->roles()->syncWithoutDetaching([$superRole->id]);

        // 3. Akun Admin Lama
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@ams.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin@ams.com'),
            ]
        );
        $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);

        // 4. Daftar 2 Akun Admin Baru
        $newAdmins = [
            [
                'name' => 'Admin IT',
                'email' => 'adminit@plnsc.co.id',
            ],
            [
                'name' => 'Admin SDM',
                'email' => 'adminsdm@plnsc.co.id',
            ],
        ];

        // 5. Loop untuk menambahkan akun baru dengan Role Admin
        foreach ($newAdmins as $admin) {
            $user = User::firstOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'password' => Hash::make($admin['email']), // Password sama dengan email
                ]
            );
            
            // Assign role Admin ke akun tersebut
            $user->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        // 6. Akun Mentor (data peserta dibatasi ke divisi mentor: users.division_id)
        $mentors = [
            ['name' => 'Mentor IT',  'email' => 'mentorit@plnsc.co.id',  'division' => 'Perencanaan dan Pengembangan Bisnis'],
            ['name' => 'Mentor SDM', 'email' => 'mentorsdm@plnsc.co.id', 'division' => 'Sumber Daya Manusia'],
        ];

        foreach ($mentors as $m) {
            $division = Division::firstOrCreate(['name' => $m['division']]);

            $mentorUser = User::firstOrCreate(
                ['email' => $m['email']],
                [
                    'name' => $m['name'],
                    'password' => Hash::make($m['email']), // Password sama dengan email
                ]
            );
            $mentorUser->division_id = $division->id; // sinkron ulang jika seeder dijalankan lagi
            $mentorUser->save();
            $mentorUser->roles()->syncWithoutDetaching([$mentorRole->id]);
        }
    }
}