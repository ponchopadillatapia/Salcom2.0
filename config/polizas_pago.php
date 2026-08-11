<?php

/**
 * Pólizas Contpaqi-style para "Pago a proveedores".
 * serie = lo que asignan en Contpaqi (8969 / 2026).
 */
return [
    '8969_mxn' => [
        'key' => '8969_mxn',
        'serie' => '8969',
        'concepto' => 'Pagos nacionales MXN',
        'titulo' => '8969 — Nacionales MXN',
        'descripcion' => 'Pagos nacionales de proveedores nacionales en pesos (MXN).',
        'moneda' => 'MXN',
        'moneda_label' => 'PESO MEXICANO',
        'ambito' => 'nacional_mxn',
        'tipo_cambio_default' => 1,
        'color' => '#166534',
    ],
    '8969_aduanal' => [
        'key' => '8969_aduanal',
        'serie' => '8969',
        'concepto' => 'Agente aduanal (pesos)',
        'titulo' => '8969 — Agente aduanal',
        'descripcion' => 'Agentes aduanales a quienes se les paga en pesos.',
        'moneda' => 'MXN',
        'moneda_label' => 'PESO MEXICANO',
        'ambito' => 'aduanal',
        'tipo_cambio_default' => 1,
        'color' => '#0f766e',
    ],
    '2026_base' => [
        'key' => '2026_base',
        'serie' => '2026',
        'concepto' => 'Banco Base Dollar',
        'titulo' => '2026 — Banco Base Dollar',
        'descripcion' => 'Proveedores nacionales con cuenta en dólares y banco en México.',
        'moneda' => 'USD',
        'moneda_label' => 'DOLAR AMERICANO',
        'ambito' => 'nacional_usd',
        'tipo_cambio_default' => null,
        'color' => '#1d4ed8',
    ],
    '2026_extranjera' => [
        'key' => '2026_extranjera',
        'serie' => '2026',
        'concepto' => '2026 Extranjera',
        'titulo' => '2026 — Extranjera',
        'descripcion' => 'Proveedores que manejan banco extranjero.',
        'moneda' => 'USD',
        'moneda_label' => 'DOLAR AMERICANO',
        'ambito' => 'extranjero',
        'tipo_cambio_default' => null,
        'color' => '#7c3aed',
    ],
];
