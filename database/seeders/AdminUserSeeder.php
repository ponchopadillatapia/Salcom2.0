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
                'nombre'   => 'Administrador',
                'correo'   => 'admin@salcom.com',
                'activo'   => true,
                'rol'      => 'gerente',
            ],
            [
                'usuario'  => 'BRENDA',
                'password' => Hash::make('salcom2026'),
                'nombre'   => 'Brenda',
                'correo'   => 'brenda@salcom.com',
                'activo'   => true,
                'rol'      => 'gerente',
            ],
            [
                'usuario'  => 'ALEJANDRA',
                'password' => Hash::make('salcom2026'),
                'nombre'   => 'Alejandra',
                'correo'   => 'alejandra@salcom.com',
                'activo'   => true,
                'rol'      => 'materia_prima',
            ],
            [
                'usuario'  => 'ROSY',
                'password' => Hash::make('salcom2026'),
                'nombre'   => 'Rosy',
                'correo'   => 'rosy@salcom.com',
                'activo'   => true,
                'rol'      => 'material_empaque',
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
