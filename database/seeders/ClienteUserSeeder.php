<?php

namespace Database\Seeders;

use App\Models\ClienteUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClienteUserSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = [
            [
                'usuario'            => 'CLI001',
                'password'           => Hash::make('salcom2026'),
                'nombre'             => 'Comercializadora del Norte SA de CV',
                'tipo_persona'       => 'Persona Moral',
                'tipo_cliente'       => 'mayorista',
                'codigo_cliente'     => 'CLI-2026-001',
                'correo'             => 'compras@comnorte.com',
                'telefono'           => '8112345678',
                'rfc'                => 'CNO260101AAA',
                'credito_autorizado' => false,
                'activo'             => true,
            ],
            [
                'usuario'            => 'CLI002',
                'password'           => Hash::make('salcom2026'),
                'nombre'             => 'Ferretería López',
                'tipo_persona'       => 'Persona Física',
                'tipo_cliente'       => 'minorista',
                'codigo_cliente'     => 'CLI-2026-002',
                'correo'             => 'contacto@ferrelopez.com',
                'telefono'           => '3387654321',
                'rfc'                => 'LOPJ900101BBB',
                'credito_autorizado' => false,
                'activo'             => true,
            ],
        ];

        foreach ($clientes as $c) {
            ClienteUser::updateOrCreate(['usuario' => $c['usuario']], $c);
        }
    }
}
