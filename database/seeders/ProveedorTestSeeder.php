<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ProveedorTestSeeder extends Seeder
{
    public function run(): void
    {
        $datos = [
            'usuario' => 'proveedor.test',
            'password' => Hash::make('password123'),
            'nombre' => 'Proveedor de Prueba S.A. de C.V.',
            'tipo_persona' => 'Persona Moral',
            'telefono' => '3312345678',
            'correo' => 'proveedor@test.com',
            'activo' => true,
            'score_entrega' => 85.00,
            'score_puntualidad' => 90.00,
            'score_total' => 87.50,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('proveedores_users', 'id_proveedor')) {
            $datos['id_proveedor'] = 'PROV-TEST-001';
        } elseif (Schema::hasColumn('proveedores_users', 'codigo_compras')) {
            $datos['codigo_compras'] = 'PROV-TEST-001';
        }

        // Evitar duplicados
        $existe = DB::table('proveedores_users')
            ->where('usuario', 'proveedor.test')
            ->orWhere('correo', 'proveedor@test.com')
            ->exists();

        if (!$existe) {
            DB::table('proveedores_users')->insert($datos);
            $this->command->info('✓ Proveedor de prueba creado exitosamente.');
        } else {
            // Actualizar password por si cambió
            DB::table('proveedores_users')
                ->where('usuario', 'proveedor.test')
                ->update(['password' => Hash::make('password123'), 'activo' => true]);
            $this->command->info('✓ Proveedor ya existía, password actualizado.');
        }

        $this->command->info('');
        $this->command->info('Credenciales de acceso:');
        $this->command->info('  Usuario: proveedor.test');
        $this->command->info('  Correo:  proveedor@test.com');
        $this->command->info('  Password: password123');
        $this->command->info('');
        $this->command->info('Puedes iniciar sesión con el usuario o el correo.');
    }
}
