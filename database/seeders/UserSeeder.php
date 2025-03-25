<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Array data untuk 5 user dengan data yang berbeda
        $users = [
            [
                'uid'            => Str::random(28),
                'nama_pengguna'  => 'SnowOwl035',
                'password'       => Hash::make('White035'),
                'email'          => 'snowowl035@gmail.com',
                'profile_pic'    => null,
                'role'           => 'Penulis',
                'nama_lengkap'   => 'Willy Nugroho',
            ],
            [
                'uid'            => Str::random(28),
                'nama_pengguna'  => 'danielumar065',
                'password'       => Hash::make('Pembaca1'),
                'email'          => 'difnd573@gmail.com',
                'profile_pic'    => null,
                'role'           => 'Pembaca',
                'nama_lengkap'   => 'Daniel Umar',
            ],
            [
                'uid'            => Str::random(28),
                'nama_pengguna'  => 'Admin1',
                'password'       => Hash::make('Admin123'),
                'email'          => 'yrwvi105@gmail.com',
                'profile_pic'    => null,
                'role'           => 'Admin',
                'nama_lengkap'   => 'Admin1',
            ],
            [
                'uid'            => Str::random(28),
                'nama_pengguna'  => 'Willy05',
                'password'       => Hash::make('RedFalco030'),
                'email'          => 'reddragonflies46@gmail.com',
                'profile_pic'    => null,
                'role'           => 'Penulis',
                'nama_lengkap'   => 'Nikto Vasilevskiy',
            ],
            [
                'uid'            => Str::random(28),
                'nama_pengguna'  => 'habiburrohman093',
                'password'       => Hash::make('Pembaca2'),
                'email'          => 'ateox912@gmail.com',
                'profile_pic'    => null,
                'role'           => 'Pembaca',
                'nama_lengkap'   => 'mohammad habiburrohman',
            ],
        ];

        // Looping untuk memasukkan setiap data user ke tabel "user"
        foreach ($users as $user) {
            DB::table('user')->insert($user);
        }
    }
}
