<?php

/**
 * Copia este archivo a config.php en el servidor que SÍ ve el puerto 7183
 * (la máquina de Wiese o la de al lado, 172.16.1.250 / 172.16.1.251).
 * No lo subas a SiteGround y no lo publiques en git.
 */
return [
    // La API de Wiese, solo por la red interna. Nunca una IP pública.
    'upstream' => 'http://172.16.1.250:7183/api',

    // Mismo valor que WIESE_GATEWAY_TOKEN en el .env de SiteGround.
    // Cámbialo por una cadena larga y aleatoria.
    'token' => '',

    // Solo el servidor de SiteGround puede entrar.
    'allowed_ips' => [
        '35.215.67.229',
    ],

    // La URL pública de esta puerta termina en /api, igual que la de Wiese.
    'public_prefix' => '/api',

    'timeout' => 60,
];
