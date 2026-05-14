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

    /**
     * Categorías del catálogo (misma `seccion` / slug que `resources/views/clientes/catalogo.blade.php`).
     * Forecast, OTIF y el filtro del catálogo leen esta lista para mantener nombres alineados.
     */
    'analitica_portal' => [
        'catalogo_categorias' => [
            ['slug' => 'aerosol-8oz', 'seccion' => 'AEROSOL 8OZ'],
            ['slug' => 'aromatizante-en-aerosol', 'seccion' => 'AROMATIZANTE EN AEROSOL'],
            ['slug' => 'aromatizante-en-aerosol-8oz', 'seccion' => 'AROMATIZANTE EN AEROSOL 8OZ'],
            ['slug' => 'aromatizante-en-gel', 'seccion' => 'AROMATIZANTE EN GEL'],
            ['slug' => 'aromatizante-gel-cono', 'seccion' => 'AROMATIZANTE GEL CONO'],
            ['slug' => 'aromatizante-liquido', 'seccion' => 'AROMATIZANTE LÍQUIDO'],
            ['slug' => 'aromatizante-para-auto', 'seccion' => 'AROMATIZANTE PARA AUTO'],
            ['slug' => 'aromatizante-para-closet', 'seccion' => 'AROMATIZANTE PARA CLOSET'],
            ['slug' => 'aromatizantes-en-aerosol', 'seccion' => 'AROMATIZANTES EN AEROSOL'],
            ['slug' => 'aromatizantes-en-aerosol-400ml', 'seccion' => 'AROMATIZANTES EN AEROSOL 400ML'],
            ['slug' => 'bicarbonato', 'seccion' => 'BICARBONATO'],
            ['slug' => 'clip-on', 'seccion' => 'CLIP ON'],
            ['slug' => 'difusor-electrico', 'seccion' => 'DIFUSOR ELÉCTRICO'],
            ['slug' => 'hang-air', 'seccion' => 'HANG AIR'],
            ['slug' => 'linea-premium', 'seccion' => 'LINEA PREMIUM'],
            ['slug' => 'liquido-limpiador-para-sanitarios', 'seccion' => 'LÍQUIDO LIMPIADOR PARA SANITARIOS'],
            ['slug' => 'lo-nuevo', 'seccion' => 'LO NUEVO'],
            ['slug' => 'productos-non-para', 'seccion' => 'PRODUCTOS NON-PARA'],
            ['slug' => 'productos-para', 'seccion' => 'PRODUCTOS PARA'],
            ['slug' => 'repuesto-difusor-electrico', 'seccion' => 'REPUESTO DIFUSOR ELÉCTRICO'],
            ['slug' => 'tapetes-urinal-screens', 'seccion' => 'TAPETES / URINAL SCREENS'],
        ],

        /**
         * Dashboard — “Categorías más vendidas”: mismas secciones que el filtro del catálogo.
         * `img` = archivo en public/Catalogo (mismo criterio que catalogo.blade.php).
         */
        'dashboard_categorias_mas_vendidas' => [
            ['seccion' => 'AROMATIZANTES EN AEROSOL 400ML', 'slug' => 'aromatizantes-en-aerosol-400ml', 'img' => 'Naeho 57 a 10.jpg', 'ud' => 2840, 'bar' => 100, 'sube' => true, 'pct' => 12.4, 'fecha' => '01/05/2026'],
            ['seccion' => 'AROMATIZANTE EN AEROSOL', 'slug' => 'aromatizante-en-aerosol', 'img' => 'Naedc 28 a 43.jpg', 'ud' => 2210, 'bar' => 78, 'sube' => true, 'pct' => 5.2, 'fecha' => '01/05/2026'],
            ['seccion' => 'AROMATIZANTE LÍQUIDO', 'slug' => 'aromatizante-liquido', 'img' => 'Nlilg 48 a 53.jpg', 'ud' => 1680, 'bar' => 59, 'sube' => false, 'pct' => 3.1, 'fecha' => '28/04/2026'],
            ['seccion' => 'TAPETES / URINAL SCREENS', 'slug' => 'tapetes-urinal-screens', 'img' => 'Ntaas 25 a 34.jpg', 'ud' => 1420, 'bar' => 50, 'sube' => false, 'pct' => 8.7, 'fecha' => '28/04/2026'],
            ['seccion' => 'DIFUSOR ELÉCTRICO', 'slug' => 'difusor-electrico', 'img' => 'Ndiel 00 a 03.jpg', 'ud' => 960, 'bar' => 34, 'sube' => true, 'pct' => 2.0, 'fecha' => '30/04/2026'],
            ['seccion' => 'AROMATIZANTE PARA AUTO', 'slug' => 'aromatizante-para-auto', 'img' => 'Narau09 a 12.jpg', 'ud' => 520, 'bar' => 18, 'sube' => false, 'pct' => 11.0, 'fecha' => '25/04/2026'],
        ],

        'forecast' => [
            'alza' => [
                ['seccion' => 'AROMATIZANTES EN AEROSOL 400ML', 'slug' => 'aromatizantes-en-aerosol-400ml', 'score' => 94, 'trend' => '+14%'],
                ['seccion' => 'DIFUSOR ELÉCTRICO', 'slug' => 'difusor-electrico', 'score' => 89, 'trend' => '+9%'],
                ['seccion' => 'CLIP ON', 'slug' => 'clip-on', 'score' => 85, 'trend' => '+6%'],
                ['seccion' => 'LINEA PREMIUM', 'slug' => 'linea-premium', 'score' => 82, 'trend' => '+4%'],
                ['seccion' => 'AROMATIZANTE LÍQUIDO', 'slug' => 'aromatizante-liquido', 'score' => 78, 'trend' => '+3%'],
            ],
            'baja' => [
                ['seccion' => 'PRODUCTOS NON-PARA', 'slug' => 'productos-non-para', 'score' => 56, 'trend' => '-12%'],
                ['seccion' => 'AROMATIZANTE EN GEL', 'slug' => 'aromatizante-en-gel', 'score' => 61, 'trend' => '-6%'],
                ['seccion' => 'TAPETES / URINAL SCREENS', 'slug' => 'tapetes-urinal-screens', 'score' => 64, 'trend' => '-4%'],
                ['seccion' => 'AROMATIZANTES EN AEROSOL', 'slug' => 'aromatizantes-en-aerosol', 'score' => 67, 'trend' => '-2%'],
                ['seccion' => 'HANG AIR', 'slug' => 'hang-air', 'score' => 71, 'trend' => '-1%'],
            ],
            'distribucion_secciones' => [
                ['seccion' => 'AROMATIZANTES EN AEROSOL 400ML', 'pct' => 27],
                ['seccion' => 'AROMATIZANTE EN AEROSOL', 'pct' => 21],
                ['seccion' => 'AROMATIZANTE LÍQUIDO', 'pct' => 15],
                ['seccion' => 'TAPETES / URINAL SCREENS', 'pct' => 12],
                ['seccion' => 'DIFUSOR ELÉCTRICO', 'pct' => 10],
                ['seccion' => 'Resto de categorías (catálogo)', 'pct' => 15],
            ],
            'tendencia_mensual' => [
                'meses' => ['Nov', 'Dic', 'Ene', 'Feb', 'Mar', 'Abr'],
                'series' => [
                    ['seccion' => 'AROMATIZANTES EN AEROSOL 400ML', 'unidades' => [5200, 5480, 5920, 5750, 6280, 6100]],
                    ['seccion' => 'AROMATIZANTE EN AEROSOL', 'unidades' => [3400, 3520, 3610, 3580, 3720, 3690]],
                    ['seccion' => 'AROMATIZANTE LÍQUIDO', 'unidades' => [2100, 2080, 2240, 2190, 2360, 2310]],
                    ['seccion' => 'Otros (catálogo)', 'unidades' => [1180, 1210, 1240, 1190, 1320, 1280]],
                ],
            ],
        ],

        'otif' => [
            'on_time' => [
                ['pedido' => 'PED-2026-005', 'seccion' => 'AROMATIZANTES EN AEROSOL 400ML', 'producto' => 'Aerosol HO WIESE Thaití 365g/400ml C/12 pzas', 'compromiso' => '01/05/2026', 'entrega' => '01/05/2026', 'diff' => 0],
                ['pedido' => 'PED-2026-004', 'seccion' => 'AROMATIZANTE EN AEROSOL', 'producto' => 'Aerosol DC WIESE Lavanda 180g/256ml C/12 pzas', 'compromiso' => '28/04/2026', 'entrega' => '27/04/2026', 'diff' => 1],
                ['pedido' => 'PED-2026-002', 'seccion' => 'DIFUSOR ELÉCTRICO', 'producto' => 'Difusor Eléctrico WIESE Brissé 21ml C/12 pzas', 'compromiso' => '25/04/2026', 'entrega' => '28/04/2026', 'diff' => -3],
                ['pedido' => 'PED-2026-001', 'seccion' => 'AROMATIZANTE LÍQUIDO', 'producto' => 'Líquido Goteador WIESE Mango 270g C/6 pzas', 'compromiso' => '22/04/2026', 'entrega' => '22/04/2026', 'diff' => 0],
                ['pedido' => 'PED-2025-118', 'seccion' => 'CLIP ON', 'producto' => 'Aromatizante Clip On WIESE Mango-Naranja C/12 pzas', 'compromiso' => '18/04/2026', 'entrega' => '19/04/2026', 'diff' => -1],
            ],
            'in_full' => [
                ['pedido' => 'PED-2026-005', 'seccion' => 'AROMATIZANTES EN AEROSOL 400ML', 'producto' => 'Aerosol HO WIESE Thaití 365g/400ml C/12 pzas', 'solicitado' => '120 cajas', 'entregado' => '120 cajas', 'pct' => 100, 'ok' => true],
                ['pedido' => 'PED-2026-004', 'seccion' => 'AROMATIZANTE EN AEROSOL', 'producto' => 'Aerosol DC WIESE Lavanda 180g/256ml C/12 pzas', 'solicitado' => '80 cajas', 'entregado' => '80 cajas', 'pct' => 100, 'ok' => true],
                ['pedido' => 'PED-2026-002', 'seccion' => 'DIFUSOR ELÉCTRICO', 'producto' => 'Difusor Eléctrico WIESE Brissé 21ml C/12 pzas', 'solicitado' => '40 cajas', 'entregado' => '40 cajas', 'pct' => 100, 'ok' => true],
                ['pedido' => 'PED-2026-001', 'seccion' => 'PRODUCTOS NON-PARA', 'producto' => 'Pastilla Cloro WIESE 35g C/12 pzas', 'solicitado' => '200 cajas', 'entregado' => '120 cajas', 'pct' => 60, 'ok' => false],
                ['pedido' => 'PED-2025-118', 'seccion' => 'AROMATIZANTE LÍQUIDO', 'producto' => 'Líquido Goteador WIESE Cítrus 270g C/6 pzas', 'solicitado' => '60 cajas', 'entregado' => '60 cajas', 'pct' => 100, 'ok' => true],
            ],
        ],
    ],
];
