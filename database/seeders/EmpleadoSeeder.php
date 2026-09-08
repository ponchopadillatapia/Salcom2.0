<?php

namespace Database\Seeders;

use App\Models\Empleado;
use Illuminate\Database\Seeder;

class EmpleadoSeeder extends Seeder
{
    public function run(): void
    {
        Empleado::updateOrCreate(
            ['numero_empleado' => '31542'],
            ['nombre' => 'Alfonso Padilla', 'departamento' => 'Promotor', 'activo' => true]
        );
    }
}
