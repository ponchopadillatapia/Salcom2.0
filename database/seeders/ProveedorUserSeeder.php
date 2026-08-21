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
                'usuario' => 'PROV001',
                'password' => Hash::make('salcom2026'),
                'id_proveedor' => '102003240',
                'nombre' => 'Distribuidora Nacional SA de CV',
                'tipo_persona' => 'Persona Moral',
                'telefono' => '3312345678',
                'correo' => 'contacto@distribuidora.com',
            ],
            [
                'usuario' => 'PROV002',
                'password' => Hash::make('salcom2026'),
                'id_proveedor' => '102003241',
                'nombre' => 'Materiales Industriales del Bajío',
                'tipo_persona' => 'Persona Moral',
                'telefono' => '4771234567',
                'correo' => 'ventas@mibajio.com',
            ],
            [
                'usuario' => 'PROV003',
                'password' => Hash::make('salcom2026'),
                'id_proveedor' => '102003242',
                'nombre' => 'Juan Pérez López',
                'tipo_persona' => 'Persona Física',
                'telefono' => '5551234567',
                'correo' => 'juan.perez@correo.com',
            ],
            // Demos para alta nacional ME/MP (hasta conectar proveedores reales)
            [
                'usuario' => 'said',
                'password' => Hash::make('salcom2026'),
                'id_proveedor' => 'DEMO-SAID',
                'nombre' => 'Proveedor Demo Said',
                'tipo_persona' => 'Persona Moral',
                'telefono' => '3300000001',
                'correo' => 'said@demo.salcom',
                'activo' => true,
            ],
            [
                'usuario' => 'demo',
                'password' => Hash::make('salcom2026'),
                'id_proveedor' => 'DEMO-001',
                'nombre' => 'Proveedor Demo',
                'tipo_persona' => 'Persona Moral',
                'telefono' => '3300000002',
                'correo' => 'demo@demo.salcom',
                'activo' => true,
            ],
            [
                'usuario' => 'rebeca',
                'password' => Hash::make('Re.123'),
                'id_proveedor' => 'DEMO-REBECA',
                'nombre' => 'Rebeca (Test)',
                'tipo_persona' => 'Persona Moral',
                'telefono' => '3300000003',
                'correo' => 'rebeca@wiese.com.mx',
                'activo' => true,
            ],
        ];

        foreach ($proveedores as $prov) {
            ProveedorUser::updateOrCreate(
                ['usuario' => $prov['usuario']],
                array_merge(['activo' => true], $prov)
            );
        }
    }
}
