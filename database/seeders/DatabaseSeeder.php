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

        $this->call(RoleSeeder::class);


        User::factory()->create([
            'name' => 'Admin Unhas',
            'email' => 'adminsikola@unhas.ac.id',
            'username' => 'admin_unhas',
            'password' => Hash::make('unH@$'),
            'role_id' => 1,
            'fakultas_id' => 0,
            'nama_fakultas' => 'Universitas Hasanuddin',


        ]);


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
        $createdUsersProdi = [];

        // Create users for each program
        $facultyData = [];
        $programData = [];
        foreach ($programs as $program) {
            if (stripos($program['nama_resmi'], 'hapus') === false && stripos($program['nama_resmi'], 'Testing') === false) {
                # code...

                $faculty = $program['fakultas'];
                $facultyUsername = 'admin_' . strtolower($faculty['nama_singkat']);
                $facultyPassword = $credentials[$facultyUsername] ?? 'defaultPassword';

                $facultyData[] = [
                    'name' => $faculty['nama_resmi'],
                    'email' => null,
                    'username' => $facultyUsername,
                    'password' => Hash::make($facultyPassword),
                    'fakultas_id' => $faculty['id'],
                    'nama_fakultas' => $faculty['nama_resmi'],
                    'role_id' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $namProdi = str_replace(' ', '_', strtolower($program['nama_resmi']));
                $namProdi = str_replace('-', '', $namProdi);
                $programUsername = 'admin_' . $namProdi;
                $programPassword = \Illuminate\Support\Str::random(6);
                $programData[] = [
                    'name' => $program['nama_resmi'],
                    'email' => null,
                    'username' => $programUsername,
                    'password' => Hash::make($programPassword),
                    'fakultas_id' => $faculty['id'],
                    'nama_fakultas' => $faculty['nama_resmi'],
                    'prodi_id' => $program['id'],
                    'role_id' => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $createdUsers[] = [
                    'name' => $faculty['nama_resmi'],
                    'username' => $facultyUsername,
                    'password' => $facultyPassword,
                    'role' => 'fakultas',
                ];
                $createdUsersProdi[] = [
                    'name' => $program['nama_resmi'],
                    'username' => $programUsername,
                    'password' => $programPassword,
                    'role' => 'prodi',
                ];

            }
        }

        // Insert faculty records. Use insertOrIgnore to skip duplicates.
        DB::table('users')->insertOrIgnore($facultyData);

        // Insert program records.
        DB::table('users')->insertOrIgnore($programData);

        $csvFile = storage_path('app/private/hasilCreateUserProdi.csv');
        $handle = fopen($csvFile, 'w');


        // Write CSV header.
        fputcsv($handle, ['name', 'username', 'password', 'role']);
        // Write each user row.
        foreach ($createdUsersProdi as $userData) {
            fputcsv($handle, [
                $userData['name'],
                $userData['username'],
                $userData['password'],
                $userData['role']
            ]);
        }
        fclose($handle);


        // Optionally, write created users data to a file
        // File::put(database_path('seeders/hasilCreateUserFakultas.json'), json_encode($createdUsers, JSON_PRETTY_PRINT));
        // File::put(database_path('seeders/hasilCreateUserProdi.json'), json_encode($createdUsersProdi, JSON_PRETTY_PRINT));

    }
}
