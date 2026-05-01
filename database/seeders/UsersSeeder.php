<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Criar usuários mecanicos
        $mecanicos = [
            [
                'name' => 'João Silva',
                'email' => 'joao.silva@oficina.local',
                'password' => Hash::make('mecanico123'),
                'role' => 'mecanico',
                'especialidade' => 'Motor',
            ],
            [
                'name' => 'Carlos Santos',
                'email' => 'carlos.santos@oficina.local',
                'password' => Hash::make('mecanico123'),
                'role' => 'mecanico',
                'especialidade' => 'Suspensão e Freios',
            ],
            [
                'name' => 'Pedro Oliveira',
                'email' => 'pedro.oliveira@oficina.local',
                'password' => Hash::make('mecanico123'),
                'role' => 'mecanico',
                'especialidade' => 'Elétrica',
            ],
        ];

        foreach ($mecanicos as $mecanico) {
            $especialidade = $mecanico['especialidade'];
            unset($mecanico['especialidade']);

            $user = DB::table('users')->updateOrInsert(
                ['email' => $mecanico['email']],
                [
                    'name' => $mecanico['name'],
                    'password' => $mecanico['password'],
                    'role' => 'mecanico',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $userId = DB::table('users')->where('email', $mecanico['email'])->first()->id;

            DB::table('mecanicos')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'especialidade' => $especialidade,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // Criar usuários atendentes
        $atendentes = [
            [
                'name' => 'Maria Costa',
                'email' => 'maria.costa@oficina.local',
                'password' => Hash::make('atendente123'),
                'role' => 'atendente',
            ],
            [
                'name' => 'Ana Ferreira',
                'email' => 'ana.ferreira@oficina.local',
                'password' => Hash::make('atendente123'),
                'role' => 'atendente',
            ],
            [
                'name' => 'Juliana Martins',
                'email' => 'juliana.martins@oficina.local',
                'password' => Hash::make('atendente123'),
                'role' => 'atendente',
            ],
        ];

        foreach ($atendentes as $atendente) {
            DB::table('users')->updateOrInsert(
                ['email' => $atendente['email']],
                [
                    'name' => $atendente['name'],
                    'password' => $atendente['password'],
                    'role' => 'atendente',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
