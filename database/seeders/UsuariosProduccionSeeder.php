<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Usuarios reales de producción para Wiese/Salcom.
 */
class UsuariosProduccionSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            // Compras y mantenimiento (alta de producto)
            [
                'nombre' => 'Brenda Pliego',
                'correo' => 'brenda.pliego@wiese.com.mx',
                'usuario' => 'brenda.pliego',
                'password' => Hash::make('Br.123'),
                'rol' => 'compras_nacional',
                'activo' => true,
            ],
            [
                'nombre' => 'Blanca Paganoni',
                'correo' => 'blanca.paganoni@wiese.com.mx',
                'usuario' => 'blanca.paganoni',
                'password' => Hash::make('Bl.123'),
                'rol' => 'mantenimiento',
                'activo' => true,
            ],
            [
                'nombre' => 'Acela Bolaños',
                'correo' => 'acela.bolanos@wiese.com.mx',
                'usuario' => 'acela.bolanos',
                'password' => Hash::make('Ac.123'),
                'rol' => 'compras_importacion',
                'activo' => true,
            ],
            // Admins (dueños — ven todo)
            [
                'nombre' => 'Fred Cominu',
                'correo' => 'fredcominu@wiese.com.mx',
                'usuario' => 'fredcominu',
                'password' => Hash::make('Co.123'),
                'rol' => 'admin',
                'activo' => true,
            ],
            [
                'nombre' => 'Alex Salazar',
                'correo' => 'alex.salazar@wiese.com.mx',
                'usuario' => 'alex.salazar',
                'password' => Hash::make('Alex.178'),
                'rol' => 'admin',
                'activo' => true,
            ],
            [
                'nombre' => 'Jesús Espinoza',
                'correo' => 'jesus.espinoza@wiese.com.mx',
                'usuario' => 'jesus.espinoza',
                'password' => Hash::make('Jess.589'),
                'rol' => 'admin',
                'activo' => true,
            ],
        ];

        foreach ($usuarios as $u) {
            AdminUser::updateOrCreate(
                ['correo' => $u['correo']],
                $u
            );
        }

        echo "✅ 6 usuarios de producción creados.\n";
        echo "   - 3 compras/mantenimiento (alta de producto)\n";
        echo "   - 3 admins (ven todo)\n";
    }
}
