<?php

declare(strict_types=1);

$configFile = dirname(__DIR__).'/config.php';
if (! is_file($configFile)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Puerta sin configurar']);
    exit;
}

/** @var array<string, mixed> $config */
$config = require $configFile;

require dirname(__DIR__).'/src/Gateway.php';

$gateway = WieseGateway\Gateway::fromConfig($config);

$headers = [];
if (function_exists('getallheaders')) {
    foreach (getallheaders() ?: [] as $nombre => $valor) {
        $headers[(string) $nombre] = (string) $valor;
    }
}

$resultado = $gateway->handle(
    (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
    (string) ($_SERVER['REQUEST_URI'] ?? '/'),
    $headers,
    (string) file_get_contents('php://input'),
    (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
);

http_response_code($resultado['status']);
header('Content-Type: '.$resultado['content_type'].'; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
echo $resultado['body'];
