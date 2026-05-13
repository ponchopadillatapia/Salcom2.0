<?php

/**
 * Datos demo del portal de clientes (carrito / historial en localStorage hasta API).
 */
return [
    'historial_pedidos' => [
        'storage_key' => 'salcom_cliente_pedidos_v1',
        'seed' => [
            [
                'folio' => 'PED-2026-001',
                'fecha' => '01/04/2026',
                'diaEnviado' => '03/04/2026',
                'diaLlegada' => '05/04/2026',
                'pago' => 'contado',
                'estatus' => 'entregado',
                'key' => 'entregado',
                'total' => 8450.0,
                'lineas' => [
                    ['codigo' => 'DET-IND', 'nombre' => 'Detergente Industrial', 'cantidad' => 10, 'precioUnit' => 500],
                    ['codigo' => 'DES-HD', 'nombre' => 'Desengrasante HD', 'cantidad' => 5, 'precioUnit' => 690],
                ],
            ],
            [
                'folio' => 'PED-2026-002',
                'fecha' => '03/04/2026',
                'diaEnviado' => '12/04/2026',
                'diaLlegada' => '',
                'pago' => 'contado',
                'estatus' => 'enviado',
                'key' => 'enviado',
                'total' => 2670.0,
                'lineas' => [
                    ['codigo' => 'ACE-SAE40', 'nombre' => 'Aceite Lubricante SAE 40', 'cantidad' => 3, 'precioUnit' => 890],
                ],
            ],
            [
                'folio' => 'PED-2026-003',
                'fecha' => '05/04/2026',
                'diaEnviado' => '',
                'diaLlegada' => '',
                'pago' => 'contado',
                'estatus' => 'produccion',
                'key' => 'produccion',
                'total' => 4725.0,
                'lineas' => [
                    ['codigo' => 'CIN-EMP', 'nombre' => 'Cinta Empaque', 'cantidad' => 50, 'precioUnit' => 55],
                    ['codigo' => 'STR-FILM', 'nombre' => 'Stretch Film', 'cantidad' => 20, 'precioUnit' => 98.75],
                ],
            ],
            [
                'folio' => 'PED-2026-004',
                'fecha' => '07/04/2026',
                'diaEnviado' => '',
                'diaLlegada' => '',
                'pago' => 'contado',
                'estatus' => 'autorizado',
                'key' => 'autorizado',
                'total' => 5850.0,
                'lineas' => [
                    ['codigo' => 'SAN-MUL', 'nombre' => 'Sanitizante Multiusos', 'cantidad' => 30, 'precioUnit' => 195],
                ],
            ],
            [
                'folio' => 'PED-2026-005',
                'fecha' => '09/04/2026',
                'diaEnviado' => '',
                'diaLlegada' => '',
                'pago' => 'contado',
                'estatus' => 'validacion',
                'key' => 'validacion',
                'total' => 4700.0,
                'lineas' => [
                    ['codigo' => 'SOL-DIEL', 'nombre' => 'Solvente Dieléctrico', 'cantidad' => 8, 'precioUnit' => 400],
                    ['codigo' => 'REF-IND', 'nombre' => 'Refrigerante', 'cantidad' => 2, 'precioUnit' => 750],
                ],
            ],
        ],
    ],
];
