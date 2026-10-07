<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // POR QUÉ solo este seeder: la base debe quedar LIMPIA para producción, con los
        // usuarios reales de Wiese/Salcom y SIN datos de prueba (proveedores/clientes/facturas fake).
        // Los seeders de prueba (ProveedorUserSeeder, ClienteUserSeeder, DatosPruebaSeeder, etc.)
        // ya NO se corren aquí para no reinyectar basura al hacer `migrate:fresh --seed`.
        // Si en algún momento quieres datos de prueba en local, corre ese seeder a mano:
        //   php artisan db:seed --class=DatosPruebaSeeder
        $this->call(UsuariosProduccionSeeder::class);
    }
}
