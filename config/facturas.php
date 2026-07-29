<?php

return [

    /*
    |--------------------------------------------------------------------------
    | RFC receptor (Industrias Salcom)
    |--------------------------------------------------------------------------
    */
    'rfc_receptor' => env('SALCOM_RFC', ''),

    'razon_social_receptor' => env('SALCOM_RAZON_SOCIAL', 'INDUSTRIAS SALCOM'),

    /*
    |--------------------------------------------------------------------------
    | Catálogo c_RegimenFiscal (SAT) — Física / Moral
    |--------------------------------------------------------------------------
    */
    'regimenes' => [
        '601' => ['nombre' => 'General de Ley Personas Morales', 'fisica' => false, 'moral' => true],
        '603' => ['nombre' => 'Personas Morales con Fines no Lucrativos', 'fisica' => false, 'moral' => true],
        '605' => ['nombre' => 'Sueldos y Salarios e Ingresos Asimilados a Salarios', 'fisica' => true, 'moral' => false],
        '606' => ['nombre' => 'Arrendamiento', 'fisica' => true, 'moral' => false],
        '607' => ['nombre' => 'Régimen de Enajenación o Adquisición de Bienes', 'fisica' => true, 'moral' => false],
        '608' => ['nombre' => 'Demás ingresos', 'fisica' => true, 'moral' => false],
        '610' => ['nombre' => 'Residentes en el Extranjero sin Establecimiento Permanente en México', 'fisica' => true, 'moral' => true],
        '611' => ['nombre' => 'Ingresos por Dividendos (socios y accionistas)', 'fisica' => true, 'moral' => false],
        '612' => ['nombre' => 'Personas Físicas con Actividades Empresariales y Profesionales', 'fisica' => true, 'moral' => false],
        '614' => ['nombre' => 'Ingresos por intereses', 'fisica' => true, 'moral' => false],
        '615' => ['nombre' => 'Régimen de los ingresos por obtención de premios', 'fisica' => true, 'moral' => false],
        '616' => ['nombre' => 'Sin obligaciones fiscales', 'fisica' => true, 'moral' => false],
        '620' => ['nombre' => 'Sociedades Cooperativas de Producción que optan por diferir sus ingresos', 'fisica' => false, 'moral' => true],
        '621' => ['nombre' => 'Incorporación Fiscal', 'fisica' => true, 'moral' => false],
        '622' => ['nombre' => 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras', 'fisica' => false, 'moral' => true],
        '623' => ['nombre' => 'Opcional para Grupos de Sociedades', 'fisica' => false, 'moral' => true],
        '624' => ['nombre' => 'Coordinados', 'fisica' => false, 'moral' => true],
        '625' => ['nombre' => 'Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas', 'fisica' => true, 'moral' => false],
        '626' => ['nombre' => 'Régimen Simplificado de Confianza', 'fisica' => true, 'moral' => true],
    ],

    /** Compat: nombres planos para vistas / mensajes */
    'regimenes_aceptados' => [
        '601' => 'General de Ley Personas Morales',
        '603' => 'Personas Morales con Fines no Lucrativos',
        '605' => 'Sueldos y Salarios e Ingresos Asimilados a Salarios',
        '606' => 'Arrendamiento',
        '607' => 'Régimen de Enajenación o Adquisición de Bienes',
        '608' => 'Demás ingresos',
        '610' => 'Residentes en el Extranjero sin Establecimiento Permanente en México',
        '611' => 'Ingresos por Dividendos (socios y accionistas)',
        '612' => 'Personas Físicas con Actividades Empresariales y Profesionales',
        '614' => 'Ingresos por intereses',
        '615' => 'Régimen de los ingresos por obtención de premios',
        '616' => 'Sin obligaciones fiscales',
        '620' => 'Sociedades Cooperativas de Producción que optan por diferir sus ingresos',
        '621' => 'Incorporación Fiscal',
        '622' => 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',
        '623' => 'Opcional para Grupos de Sociedades',
        '624' => 'Coordinados',
        '625' => 'Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas',
        '626' => 'Régimen Simplificado de Confianza',
    ],

    /*
    |--------------------------------------------------------------------------
    | Catálogos SAT usados al confirmar pago (admin)
    |--------------------------------------------------------------------------
    */
    'formas_pago' => [
        '01' => '01 — Efectivo',
        '02' => '02 — Cheque nominativo',
        '03' => '03 — Transferencia electrónica de fondos',
        '04' => '04 — Tarjeta de crédito',
        '28' => '28 — Tarjeta de débito',
        '99' => '99 — Por definir',
    ],

    'metodos_pago' => [
        'PUE' => 'PUE — Pago en una sola exhibición',
        'PPD' => 'PPD — Pago en parcialidades o diferido',
    ],

    'usos_cfdi' => [
        'G01' => 'G01 — Adquisición de mercancías',
        'G02' => 'G02 — Devoluciones, descuentos o bonificaciones',
        'G03' => 'G03 — Gastos en general',
        'I01' => 'I01 — Construcciones',
        'I08' => 'I08 — Otra maquinaria y equipo',
        'P01' => 'P01 — Por definir',
        'S01' => 'S01 — Sin efectos fiscales',
    ],

    /*
    |--------------------------------------------------------------------------
    | Detección de conceptos (ClaveProdServ SAT / palabras en descripción)
    |--------------------------------------------------------------------------
    | Fletes: retención de IVA siempre, sin importar el régimen.
    | Comisiones: retención solo si el emisor es persona física.
    */
    'conceptos' => [
        'flete' => [
            'claves' => [
                '78101800', // Transporte de carga por carretera
                '78101801',
                '78101802',
                '78101803',
                '78101600',
                '78141500',
            ],
            'palabras' => ['flete', 'fletes', 'fletera', 'autotransporte', 'transporte de carga'],
        ],
        'comision' => [
            'claves' => [
                '80141500', // Servicios de gestión comercial / comisiones (aprox)
                '84121500',
            ],
            'palabras' => ['comision', 'comisión', 'comisiones'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Matriz de retenciones esperadas
    |--------------------------------------------------------------------------
    */
    'retenciones' => [

        // Flete / fletera: IVA siempre (indicación Contabilidad Salcom)
        'flete' => [
            'iva' => 0.04,
            'isr' => 0.0125,
            'requiere_retencion' => true,
        ],

        // Comisiones: solo persona física
        'comision_fisica' => [
            'iva' => 0.106667,
            'isr' => 0.10,
            'requiere_retencion' => true,
        ],

        'por_regimen' => [
            '601' => ['iva' => 0.0, 'isr' => 0.0, 'requiere_retencion' => false],
            '603' => ['iva' => 0.0, 'isr' => 0.0, 'requiere_retencion' => false],
            '626' => ['iva' => 0.0, 'isr' => 0.0, 'requiere_retencion' => false],
            '612' => ['iva' => 0.106667, 'isr' => 0.10, 'requiere_retencion' => true],
            '621' => ['iva' => 0.106667, 'isr' => 0.0125, 'requiere_retencion' => true],
            '625' => ['iva' => 0.106667, 'isr' => 0.01, 'requiere_retencion' => true],
            '606' => ['iva' => 0.106667, 'isr' => 0.10, 'requiere_retencion' => true],
            '_default' => ['iva' => 0.0, 'isr' => 0.0, 'requiere_retencion' => false],
        ],
    ],

    'tolerancia_monto' => 1.00,

    'dias_vencimiento' => 30,
];
