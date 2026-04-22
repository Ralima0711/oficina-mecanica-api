<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DefaultUserSeeder extends Seeder
{
    public function run(): void
    {
        $name = env('DEFAULT_USER_NAME', 'admin');
        $email = env('DEFAULT_USER_EMAIL', 'admin@oficina.local');
        $password = env('DEFAULT_USER_PASSWORD', 'admin123');
        $role = env('DEFAULT_USER_ROLE', 'admin');

        DB::table('users')->updateOrInsert(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => $role,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
