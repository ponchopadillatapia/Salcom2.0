<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            [
                'usuario'  => 'ADMIN001',
                'password' => Hash::make('salcom2026'),
                'nombre'   => 'Super Administrador',
                'correo'   => 'admin@salcom.com',
                'activo'   => true,
            ],
        ];

        foreach ($admins as $admin) {
            AdminUser::updateOrCreate(
                ['usuario' => $admin['usuario']],
                $admin
            );
        }
    }
}
