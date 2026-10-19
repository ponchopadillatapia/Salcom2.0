<?php

/**
 * Correos del FLUJO DE PAGO (petición de Alexa).
 *
 * POR QUÉ este archivo: tener TODOS los correos del flujo en un solo lugar, fácil de editar sin
 * tocar código. Cuando cambien correos o se agreguen departamentos, se edita AQUÍ (o en el .env).
 *
 * Los valores se leen del .env si existen; si no, usan el default de aquí. Así en producción se
 * pueden sobreescribir sin cambiar el código.
 */

return [

    // A quién se le avisa cuando un depa GENERA un formato para pago (paso 1).
    // Es quien autoriza (Alexa).
    'autorizador' => env('CORREO_AUTORIZADOR', 'alexa.salazar@wiese.com.mx'),

    // A quién se le avisa cuando Alexa AUTORIZA (para que descargue el layout y pague): Karen.
    'pagador' => env('CORREO_PAGADOR', 'karen.bravo@wiese.com'),

    // Correo del encargado de cada DEPARTAMENTO: se le avisa cuando SUS facturas quedan pagadas,
    // y también es a quien se avisa si Alexa RECHAZA su formato.
    // Las claves DEBEN coincidir con PagoProveedor::DEPARTAMENTOS.
    // Rellenar con los correos reales conforme se tengan. Vacío = no se manda (solo se loguea).
    'departamentos' => [
        'compras' => env('CORREO_DEPTO_COMPRAS', ''),
        'fletes_acatlan' => env('CORREO_DEPTO_FLETES_ACATLAN', ''),
        'imex' => env('CORREO_DEPTO_IMEX', ''),
        'mantenimiento' => env('CORREO_DEPTO_MANTENIMIENTO', 'blanca.paganoni@wiese.com'),
        'framfoods_a' => env('CORREO_DEPTO_FRAMFOODS_A', ''),
        'framfoods_b' => env('CORREO_DEPTO_FRAMFOODS_B', ''),
        'seguridad' => env('CORREO_DEPTO_SEGURIDAD', ''),
        'otros' => env('CORREO_DEPTO_OTROS', ''),
    ],

];
