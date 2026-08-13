<?php

namespace Database\Seeders;

use App\Models\Factura;
use App\Models\ProveedorUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SaidPadillaTestSeeder extends Seeder
{
    public function run(): void
    {
        // Crear proveedor Said Padilla Enterprises
        $proveedor = ProveedorUser::create([
            'usuario' => 'said.padilla',
            'password' => Hash::make('said1234'),
            'id_proveedor' => 'SAID001',
            'codigo' => 'SAID001',
            'nombre' => 'Said Padilla Enterprises',
            'moneda' => 'MXN',
            'tipo_persona' => 'moral',
            'rfc' => 'SPE230801AA1',
            'telefono' => '3312345678',
            'correo' => 'said@padillaenterprises.com',
            'correo_verified_at' => now(),
            'activo' => true,
            'solicitud_alta_estatus' => 'aprobada',
            'solicitud_alta_intentos' => 0,
            'datos_identificacion' => [
                'rfc' => 'SPE230801AA1',
                'razon_social' => 'Said Padilla Enterprises S de RL de CV',
                'banco' => 'BBVA',
                'clabe' => '012345678901234567',
                'cuenta' => '1234567890',
            ],
            'aviso_privacidad_aceptado' => true,
            'aviso_privacidad_fecha' => now(),
        ]);

        $this->command->info("Proveedor creado: {$proveedor->nombre} (ID: {$proveedor->id}, Código: SAID001)");
        $this->command->info("Login: said.padilla / said1234");

        // Crear 40 facturas pendientes con montos variados
        $conceptos = ['Materia prima', 'Insumos químicos', 'Empaques', 'Etiquetas', 'Servicios logísticos', 'Mantenimiento', 'Refacciones', 'Consultoría'];

        for ($i = 1; $i <= 40; $i++) {
            $monto = round(rand(1500, 85000) + rand(0, 99) / 100, 2);
            $iva = round($monto * 0.16, 2);
            $total = round($monto + $iva, 2);
            $fecha = now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59));

            Factura::create([
                'folio_cfdi' => 'SAID-FAC-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'uuid_cfdi' => Str::uuid()->toString(),
                'codigo_proveedor' => 'SAID001',
                'monto' => $monto,
                'monto_iva' => $iva,
                'retencion_iva' => 0,
                'retencion_isr' => 0,
                'total' => $total,
                'monto_pagado' => 0,
                'estatus' => 'pendiente',
                'fecha_vencimiento' => $fecha->copy()->addDays(30),
                'notas' => $conceptos[array_rand($conceptos)] . ' - Factura de prueba #' . $i,
                'validacion_detalle' => [
                    'serie' => 'FAC',
                    'folio' => 'SAID-FAC-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'moneda' => 'MXN',
                    'cfdi' => [
                        'serie' => 'FAC',
                        'moneda' => 'MXN',
                    ],
                ],
                'created_at' => $fecha,
                'updated_at' => $fecha,
            ]);
        }

        $this->command->info("40 facturas pendientes creadas para Said Padilla Enterprises");
    }
}
