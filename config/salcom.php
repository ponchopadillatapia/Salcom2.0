<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Módulos del portal de proveedores (rollout)
    |--------------------------------------------------------------------------
    | Onboarding y Mi Perfil siempre están disponibles.
    | Alta de facturas y Facturas siguen exigiendo cuenta activa.
    | El resto nace cerrado: pon la variable en true para abrirlo.
    */
    'portal_modulos' => [
        'inicio' => filter_var(env('PORTAL_MODULO_INICIO', true), FILTER_VALIDATE_BOOLEAN),
        'mis_productos' => filter_var(env('PORTAL_MODULO_MIS_PRODUCTOS', false), FILTER_VALIDATE_BOOLEAN),
        'alta_producto' => filter_var(env('PORTAL_MODULO_ALTA_PRODUCTO', false), FILTER_VALIDATE_BOOLEAN),
        'inventario' => filter_var(env('PORTAL_MODULO_INVENTARIO', false), FILTER_VALIDATE_BOOLEAN),
        'alta_facturas' => filter_var(env('PORTAL_MODULO_ALTA_FACTURAS', true), FILTER_VALIDATE_BOOLEAN),
        'facturas' => filter_var(env('PORTAL_MODULO_FACTURAS', true), FILTER_VALIDATE_BOOLEAN),
        'ordenes_compra' => filter_var(env('PORTAL_MODULO_ORDENES_COMPRA', false), FILTER_VALIDATE_BOOLEAN),
        'otif' => filter_var(env('PORTAL_MODULO_OTIF', false), FILTER_VALIDATE_BOOLEAN),
        'forecast' => filter_var(env('PORTAL_MODULO_FORECAST', false), FILTER_VALIDATE_BOOLEAN),
        'business' => filter_var(env('PORTAL_MODULO_BUSINESS', false), FILTER_VALIDATE_BOOLEAN),
        'ia' => filter_var(env('PORTAL_MODULO_IA', false), FILTER_VALIDATE_BOOLEAN),
        'encuesta' => filter_var(env('PORTAL_MODULO_ENCUESTA', false), FILTER_VALIDATE_BOOLEAN),
    ],

    /*
    |--------------------------------------------------------------------------
    | Usuarios internos que NO requieren confirmar correo (portal proveedor)
    |--------------------------------------------------------------------------
    | Comparación case-insensitive contra `usuario` o `correo` (parte local
    | antes de @). Staff / demo / admins de prueba.
    */
    'usuarios_sin_confirmar_correo' => [
        // Staff Wiese / Salcom
        'cintia.barrera',
        'brenda.pliego',
        'blanca.paganoni',
        'acela.bolanos',
        'fredcominu',
        'alex.salazar',
        'jesus.espinoza',
        'cinthya.martinez',
        'sandra.gutierrez',
        'aneso.cominu',
        'karen.bravo',
        // Demo
        'prov001',
        'cli001',
        // Códigos espejo admin en portal
        // (también se marca por usuario del AdminUser)
    ],

];
