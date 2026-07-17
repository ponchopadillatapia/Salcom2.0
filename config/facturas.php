<?php

return [

    /*
    |--------------------------------------------------------------------------
    | RFC receptor (Industrias Salcom)
    |--------------------------------------------------------------------------
    | Si está vacío, se omite la validación estricta del receptor.
    */
    'rfc_receptor' => env('SALCOM_RFC', ''),

    'razon_social_receptor' => env('SALCOM_RAZON_SOCIAL', 'INDUSTRIAS SALCOM'),

    /*
    |--------------------------------------------------------------------------
    | Regímenes fiscales aceptados para proveedores
    |--------------------------------------------------------------------------
    */
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
    | Matriz de retenciones esperadas
    |--------------------------------------------------------------------------
    | Tasas sobre el subtotal (base) del CFDI.
    | Ajustable por Contabilidad sin tocar código.
    */
    'retenciones' => [

        // Autotransporte / fletera: IVA 4% + ISR 1.25% (práctica habitual)
        'fletera' => [
            'iva' => 0.04,
            'isr' => 0.0125,
            'requiere_retencion' => true,
        ],

        // Por régimen cuando NO es fletera
        'por_regimen' => [
            // Persona moral general: compra de bienes/servicios sin retención por defecto
            '601' => ['iva' => 0.0, 'isr' => 0.0, 'requiere_retencion' => false],
            '603' => ['iva' => 0.0, 'isr' => 0.0, 'requiere_retencion' => false],
            '626' => ['iva' => 0.0, 'isr' => 0.0, 'requiere_retencion' => false],

            // Persona física actividad empresarial / profesional
            '612' => ['iva' => 0.106667, 'isr' => 0.10, 'requiere_retencion' => true],
            '621' => ['iva' => 0.106667, 'isr' => 0.0125, 'requiere_retencion' => true],
            '625' => ['iva' => 0.106667, 'isr' => 0.01, 'requiere_retencion' => true],
            '606' => ['iva' => 0.106667, 'isr' => 0.10, 'requiere_retencion' => true],

            // Default si el régimen no está listado
            '_default' => ['iva' => 0.0, 'isr' => 0.0, 'requiere_retencion' => false],
        ],
    ],

    /** Tolerancia en pesos para comparar importes de retención */
    'tolerancia_monto' => 1.00,

    /** Días de crédito por defecto al dar de alta */
    'dias_vencimiento' => 30,
];
