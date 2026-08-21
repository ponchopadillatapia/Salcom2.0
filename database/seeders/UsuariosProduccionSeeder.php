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
            [
                'nombre' => 'Cinthya Martinez',
                'correo' => 'cinthya.martinez@wiese.com.mx',
                'usuario' => 'cinthya.martinez',
                'password' => Hash::make('Ci.123'),
                'rol' => 'compras_importacion',
                'activo' => true,
            ],
            [
                'nombre' => 'Cintia Barrera',
                'correo' => 'cintia.barrera@wiese.com.mx',
                'usuario' => 'cintia.barrera',
                'password' => Hash::make('Ci.123'),
                'rol' => 'comercial',
                'activo' => true,
            ],
            [
                'nombre' => 'Sandra Gutierrez',
                'correo' => 'sandra.gutierrez@wiese.com.mx',
                'usuario' => 'sandra.gutierrez',
                'password' => Hash::make('sa.123'),
                'rol' => 'admin', // contabilidad — admin temporal
                'activo' => true,
            ],
            [
                'nombre' => 'Karen Bravo',
                'correo' => 'karen.bravo@wiese.com.mx',
                'usuario' => 'karen.bravo',
                'password' => Hash::make('ka.123'),
                'rol' => 'admin',
                'activo' => true,
            ],
            [
                'nombre' => 'Aneso Cominu',
                'correo' => 'aneso.cominu@salcom.mx',
                'usuario' => 'aneso.cominu',
                'password' => Hash::make('An.123'),
                'rol' => 'admin',
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
            [
                'nombre' => 'Rebeca',
                'correo' => 'rebeca.leon@framfoods.com.mx',
                'usuario' => 'Rebeca',
                'password' => Hash::make('Re.123'),
                'rol' => 'admin',
                'activo' => true,
            ],
        ];

        foreach ($usuarios as $u) {
            AdminUser::updateOrCreate(
                ['usuario' => $u['usuario']],
                $u
            );
        }

        echo '✅ '.count($usuarios)." usuarios de producción sincronizados.\n";
    }
}
