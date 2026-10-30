<?php

return [

    /*
    |--------------------------------------------------------------------------
    | RFC receptor (empresas del grupo)
    |--------------------------------------------------------------------------
    | rfc_receptor se conserva por compatibilidad. La validación del CFDI
    | acepta cualquiera de los RFC en `receptores`.
    */
    'rfc_receptor' => env('SALCOM_RFC', 'ISA951017A10'),

    'razon_social_receptor' => env('SALCOM_RAZON_SOCIAL', 'INDUSTRIAS SALCOM'),

    'receptores' => [
        env('SALCOM_RFC', 'ISA951017A10') => env('SALCOM_RAZON_SOCIAL', 'Industrias Salcom'),
        env('FRAMFOODS_RFC', 'FFO140516UG8') => env('FRAMFOODS_RAZON_SOCIAL', 'Fram Foods'),
    ],

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
    | Usos CFDI ACEPTADOS al validar una factura de proveedor.
    | POR QUÉ G01/G03: los proveedores facturan compra de mercancía/insumos (G01) o gastos (G03).
    | Cualquier otro uso (incluidos los "D" de deducciones personales) se rechaza en la validación.
    | Ajustable con la variable de entorno FACTURAS_USOS_CFDI_PERMITIDOS (separados por coma).
    */
    'usos_cfdi_permitidos' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FACTURAS_USOS_CFDI_PERMITIDOS', 'G01,G03'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Detección de conceptos (ClaveProdServ SAT = criterio principal)
    |--------------------------------------------------------------------------
    | La ClaveProdServ identifica fletes, comisiones, etc. La descripción
    | solo se usa como respaldo porque varía entre proveedores.
    | Fletes: retención de IVA 4% siempre.
    | Comisiones: retención solo si el emisor es persona física.
    */
    'conceptos' => [
        'flete' => [
            'claves' => [
                '78101800', // Transporte de carga por carretera
                '78101801',
                '78101802',
                '78101803',
                '78101600', // Transporte de carga
                '78141500', // Servicios de manejo de carga
                '78101700',
                '78101804',
                '78101900',
            ],
            'palabras' => ['flete', 'fletes', 'fletera', 'autotransporte', 'transporte de carga'],
        ],
        'comision' => [
            'claves' => [
                '80141500', // Servicios de gestión comercial
                '80141600', // Administración de negocios
                '80141700',
                '80161500', // Acuerdos de ventas
                '80161501', // Comisiones por ventas
                '84121500',
                '84101700', // Servicios de corretaje
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

        // Flete / fletera: IVA 4% siempre; ISR depende del régimen del emisor
        'flete' => [
            'iva' => 0.04,
            'isr' => 0.0,
            'requiere_retencion' => true,
        ],
        // 626 RESICO: IVA 4% + ISR 1.25%. 601/612: solo IVA 4% (ISR 0 válido).
        'flete_por_regimen' => [
            '626' => ['iva' => 0.04, 'isr' => 0.0125, 'requiere_retencion' => true],
            '612' => ['iva' => 0.04, 'isr' => 0.0, 'requiere_retencion' => true],
            '601' => ['iva' => 0.04, 'isr' => 0.0, 'requiere_retencion' => true],
        ],

        // RESICO (626)
        'resico_fisica' => [
            'iva' => 0.0,
            'isr' => 0.0125,
            'requiere_retencion' => true,
        ],
        'resico_moral' => [
            'iva' => 0.0,
            'isr' => 0.0,
            'requiere_retencion' => false,
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
            // 626 se resuelve en código (PF vs PM)
            // 612 honorarios (servicios): IVA 10.67% + ISR 10%. Mercancías: sin retención (ver servicio).
            '612' => ['iva' => 0.106667, 'isr' => 0.10, 'requiere_retencion' => true],
            '621' => ['iva' => 0.106667, 'isr' => 0.0125, 'requiere_retencion' => true],
            '625' => ['iva' => 0.106667, 'isr' => 0.01, 'requiere_retencion' => true],
            '606' => ['iva' => 0.106667, 'isr' => 0.10, 'requiere_retencion' => true],
            '_default' => ['iva' => 0.0, 'isr' => 0.0, 'requiere_retencion' => false],
        ],
    ],

    'tolerancia_monto' => 1.00,

    // Margen de redondeo al comparar cantidad facturada vs cantidad de la OC (Fase 1).
    'tolerancia_cantidad' => 0.01,

    // Timeout (segundos) para buscar la OC en Wiese. El 250 no filtra por folio y devuelve TODAS las
    // OC del proveedor (cientos) con movimientos; esa consulta tarda ~20-30s. 60 da margen. Si Alan
    // agrega filtro por folio al API, esto se podrá bajar mucho.
    'oc_timeout_seg' => (int) env('FACTURAS_OC_TIMEOUT_SEG', 60),

    // Claves ProdServ del SAT que identifican un ANTICIPO. 84111506 = "Servicios de facturación",
    // la clave estándar que el SAT usa para anticipos. Se marca la factura como anticipo (informativo
    // para contabilidad); el descuento real lo maneja la nota de crédito en Formato para pago.
    'claves_anticipo' => ['84111506'],

    // Tipo de documento (CIDDOCUMENTODE) de las FACTURAS de proveedor (compras) en Wiese/Contpaqi.
    // POR QUÉ existe: el 250 exige este parámetro al listar documentos. 19 = compras (facturas);
    // 17 = órdenes de compra. Configurable por si Contpaqi cambia el catálogo.
    'tipo_documento_compra' => (string) env('FACTURAS_TIPO_DOC_COMPRA', '19'),

    // Cuántos meses hacia atrás buscamos la OC en Wiese al cruzarla con la factura.
    // POR QUÉ existe: pedir años de historia con CIDDOCUMENTODE=17 (OC + Movimientos) cuelga el 250
    // por timeout. Una OC es cercana a la factura, así que una ventana corta basta y mantiene la
    // consulta rápida. Subir con cuidado si algún proveedor factura OC muy viejas.
    'oc_ventana_meses' => (int) env('FACTURAS_OC_VENTANA_MESES', 18),

    'dias_vencimiento' => 30,

    /*
    |--------------------------------------------------------------------------
    | Plazo de pago (portal proveedor, post-validación)
    |--------------------------------------------------------------------------
    | POR QUÉ FIJO: el proveedor YA NO elige el plazo (pondría "1 día"). El plazo de pago lo define
    | Salcom/contabilidad. Al subir, la factura se registra con este plazo por defecto y
    | fecha_vencimiento = hoy + N días. Contabilidad puede ajustarlo después si hace falta.
    | plazos_dias/plazos_dias_max quedan por compatibilidad (otras pantallas los pueden usar).
    */
    'plazo_default_dias' => (int) env('FACTURAS_PLAZO_DEFAULT_DIAS', 30),
    'plazos_dias' => [30, 45, 60, 90, 120, 150, 360],
    'plazos_dias_max' => 3650,

    /*
    |--------------------------------------------------------------------------
    | Periodo de carga (portal proveedor)
    |--------------------------------------------------------------------------
    | Si es true, solo se aceptan CFDI cuya Fecha de emisión sea del mes en curso.
    | Facturas de meses anteriores (periodo ya cerrado) se rechazan.
    */
    'solo_mes_actual' => env('FACTURAS_SOLO_MES_ACTUAL', true),

    // Validar que el RFC emisor del XML sea el del proveedor logueado.
    // Default true (seguro). Se puede apagar en pruebas con FACTURAS_VALIDAR_RFC_EMISOR=false.
    'validar_rfc_emisor' => env('FACTURAS_VALIDAR_RFC_EMISOR', true),
];
