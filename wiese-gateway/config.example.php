<?php

/**
 * Plantilla de configuración de la PUERTA hacia Wiese.
 *
 * INSTALACIÓN (en el servidor de la OFICINA que ve el 7183):
 *   1) Copia este archivo y nómbralo  config.php  (en la MISMA carpeta, fuera de public/).
 *   2) Llena 'token' con una contraseña larga inventada (~40 caracteres). La MISMA va en el
 *      .env de SiteGround (WIESE_GATEWAY_TOKEN), carácter por carácter.
 *   3) Deja 'allowed_ips' solo con la IP de SiteGround.
 *   4) NO subas config.php a git ni a SiteGround (ya está en .gitignore).
 */

return [
    // A dónde reenvía la puerta: la API de Wiese en la red interna de la oficina.
    'upstream' => 'http://172.16.1.250:7183/api',

    // Contraseña compartida entre esta puerta y SiteGround. CÁMBIALA por una larga inventada.
    // Debe ser IDÉNTICA a WIESE_GATEWAY_TOKEN en el .env de SiteGround.
    'token' => 'CAMBIA-ESTO-POR-UN-TOKEN-LARGO-INVENTADO-DE-40-CHARS',

    // Solo estas IPs pueden llamar a la puerta. En producción: la IP de SiteGround.
    'allowed_ips' => [
        '35.215.67.229', // SiteGround (IP del sitio de Salcom)
    ],

    // Prefijo público del sitio donde se publica public/ (normalmente /api).
    'public_prefix' => '/api',

    // Lista blanca de rutas que la puerta deja pasar. Cualquier otra se rechaza (404).
    // Son exactamente los endpoints que usa Salcom contra Wiese.
    'allowed_paths' => [
        '/Login/Login',
        '/ClienteProveedor/BuscarPorCodigo',
        '/ClienteProveedor/BuscarPorRFC',
        '/ClienteProveedor/ListarClienteProvedorPorCodigo',
        '/ClienteProveedor/ListarProveedorWeb',
        '/Documento/ListaDocumentosOCPorProveedor',
        '/DatoDocumentoProveedor/ListarDocumentosRFC',
    ],
];
