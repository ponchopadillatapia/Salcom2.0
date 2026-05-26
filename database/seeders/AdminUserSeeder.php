<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            [
                'nombre' => 'Aneso Cominu',
                'correo' => 'aneso.cominu@salcom.mx',
                'usuario' => 'aneso.cominu',
                'password' => Hash::make('An.123'),
                'activo' => true,
                'rol' => 'admin',
            ],
        ];

        foreach ($usuarios as $data) {
            AdminUser::updateOrCreate(
                ['usuario' => $data['usuario']],
                $data
            );
        }

        $this->command->info('✅ Usuarios admin creados/actualizados.');
    }
}
