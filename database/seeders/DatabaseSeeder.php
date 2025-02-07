<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Admin Unhas',
            'email' => 'adminsikola@unhas.ac.id',
            'username' => 'admin_unhas',
            'password' => Hash::make('unH@$'),
            'role_id' => 1,
            'fakultas_id' => 0,
            'nama_fakultas' => 'Universitas Hasanuddin',


        ]);

        $this->call(RoleSeeder::class);

        // Load JSON data
        $json = File::get(database_path('seeders/user.json'));
        $programs = json_decode($json, true);

        // Predefined usernames and passwords
        $credentials = [
            'admin_ekonomi' => 'Bk6gTG',
            'admin_hukum' => 'ySZa7z',
            'admin_kedokteran' => 'RMZ82d',
            'admin_keperawatan' => '1zBrsG',
            'admin_teknik' => 'WGxYS4',
            'admin_fikp' => 'N3K0Zg',
            'admin_fisip' => '5n2sZ1',
            'admin_fib' => 'ZwsjJa',
            'admin_pertanian' => 'TNwhzu',
            'admin_mipa' => 'eP0XMY',
            'admin_peternakan' => 'Qlw0BP',
            'admin_fkg' => 'EvyBFH',
            'admin_fkm' => 'ay1giL',
            'admin_kehutanan' => 'f9UoAc',
            'admin_farmasi' => 'IvY4Os',
            'admin_pasca' => 'YKtRhm',
            'admin_mku' => 'COrnmfpz',
            'admin_vokasi' => 'Z2vRUe',
        ];

        $createdUsers = [];

        // Create users for each program
        foreach ($programs as $program) {
            $faculty = $program['fakultas'];
            $facultyUsername = 'admin_' . strtolower($faculty['nama_singkat']);
            $facultyPassword = $credentials[$facultyUsername] ?? 'defaultPassword';

            // Create faculty user if not exists
            $facultyUser = User::firstOrCreate(
                ['email' => $faculty['email']],
                [
                    'name' => $faculty['nama_resmi'],
                    'username' => $facultyUsername,
                    'password' => Hash::make($facultyPassword),
                    'fakultas_id' => $faculty['id'],
                    'nama_fakultas' => $faculty['nama_resmi'],
                    'role_id' => 2,

                ]
            );

            // Assign faculty role

            // Log faculty user
            $createdUsers[] = [
                'name' => $faculty['nama_resmi'],
                'username' => $facultyUsername,
                'password' => $facultyPassword,
                'role_id' => 'fakultas',
            ];

            // Create program user
            $programUsername = 'admin_' . strtolower($program['id']);
            $programPassword = Str::random(6);

            $programUser = User::firstOrCreate(
                ['email' => $program['email']],
                [
                    'name' => $program['nama_resmi'],
                    'username' => $programUsername,
                    'password' => Hash::make($programPassword),
                    'fakultas_id' => $faculty['id'],
                    'nama_fakultas' => $faculty['nama_resmi'],
                    'prodi_id' => $program['id'],
                    'role_id' => 3,
                ]
            );

            // Assign program role

            // Log program user
            $createdUsers[] = [
                'name' => $program['nama_resmi'],
                'username' => $programUsername,
                'password' => $programPassword,
                'role_id' => 'prodi',
            ];
        }

        // Write created users to a JSON file
        File::put(database_path('seeders/hasilCreateUser.json'), json_encode($createdUsers, JSON_PRETTY_PRINT));
    }
}
