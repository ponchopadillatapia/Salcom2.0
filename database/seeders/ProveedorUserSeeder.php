<?php

namespace Database\Seeders;

use App\Models\ProveedorUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProveedorUserSeeder extends Seeder
{
    public function run(): void
    {
        $proveedores = [
            [
                'usuario'        => 'PROV001',
                'password'       => Hash::make('salcom2026'),
                'codigo_compras' => '102003240',
                'nombre'         => 'Distribuidora Nacional SA de CV',
                'tipo_persona'   => 'Persona Moral',
                'telefono'       => '3312345678',
                'correo'         => 'contacto@distribuidora.com',
            ],
            [
                'usuario'        => 'PROV002',
                'password'       => Hash::make('salcom2026'),
                'codigo_compras' => '102003241',
                'nombre'         => 'Materiales Industriales del Bajío',
                'tipo_persona'   => 'Persona Moral',
                'telefono'       => '4771234567',
                'correo'         => 'ventas@mibajio.com',
            ],
            [
                'usuario'        => 'PROV003',
                'password'       => Hash::make('salcom2026'),
                'codigo_compras' => '102003242',
                'nombre'         => 'Juan Pérez López',
                'tipo_persona'   => 'Persona Física',
                'telefono'       => '5551234567',
                'correo'         => 'juan.perez@correo.com',
            ],
        ];

        foreach ($proveedores as $prov) {
            ProveedorUser::updateOrCreate(
                ['usuario' => $prov['usuario']],
                $prov
            );
        }
    }
}
