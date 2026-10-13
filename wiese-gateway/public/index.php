<?php

/**
 * PUERTA (gateway) hacia la API de Wiese.
 *
 * POR QUÉ EXISTE: la API de Wiese vive en una IP interna (172.16.1.250:7183) que SiteGround
 * (donde corre el sitio de Salcom) NO puede alcanzar, y esa API no tiene seguridad propia, así
 * que no se puede publicar directo a internet. Esta puerta se instala en un servidor de la
 * OFICINA (que sí ve el 7183) y se publica por HTTPS. SiteGround le habla a la puerta; la puerta
 * valida quién llama (IP + token + ruta permitida) y, si todo cuadra, reenvía la petición al 7183.
 *
 * Flujo:  Navegador -> SiteGround (35.215.67.229) --HTTPS+token--> ESTA PUERTA --red interna--> Wiese 7183
 *
 * Seguridad (todo debe cumplirse o se rechaza):
 *   1) La IP de quien llama debe estar en allowed_ips (solo SiteGround).
 *   2) Debe traer el header X-Salcom-Gateway con el token exacto del config.php.
 *   3) La ruta pedida debe estar en la lista blanca (allowed_paths).
 *
 * El config.php (con el token real) vive FUERA de public/ para que nunca se pueda descargar.
 */

// ─────────────────────────────────────────────────────────────────────────────
// 1) Cargar configuración (está un nivel arriba de public/, no accesible por web)
// ─────────────────────────────────────────────────────────────────────────────
$configPath = __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if (! is_file($configPath)) {
    http_response_code(503);
    echo json_encode(['error' => 'Puerta sin configurar: falta config.php']);
    exit;
}

$config = require $configPath;

$upstream      = rtrim((string) ($config['upstream'] ?? ''), '/');     // http://172.16.1.250:7183/api
$token         = (string) ($config['token'] ?? '');
$allowedIps    = (array)  ($config['allowed_ips'] ?? []);
$publicPrefix  = '/' . trim((string) ($config['public_prefix'] ?? '/api'), '/'); // /api
$allowedPaths  = (array)  ($config['allowed_paths'] ?? []);

// Si no hay token o upstream configurado, no reenviamos nada (modo seguro).
if ($token === '' || $upstream === '') {
    http_response_code(503);
    echo json_encode(['error' => 'Puerta sin configurar: token o upstream vacío']);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 2) Validar IP de origen (solo SiteGround puede llamar)
// ─────────────────────────────────────────────────────────────────────────────
$ipCliente = $_SERVER['REMOTE_ADDR'] ?? '';
if (! in_array($ipCliente, $allowedIps, true)) {
    http_response_code(403);
    echo json_encode(['error' => 'IP no autorizada']);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 3) Validar token del header X-Salcom-Gateway
// ─────────────────────────────────────────────────────────────────────────────
$tokenRecibido = $_SERVER['HTTP_X_SALCOM_GATEWAY'] ?? '';
// hash_equals evita ataques de temporización al comparar el token.
if (! is_string($tokenRecibido) || ! hash_equals($token, $tokenRecibido)) {
    http_response_code(403);
    echo json_encode(['error' => 'Token invalido']);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 4) Obtener la ruta pedida y validar contra la lista blanca
// ─────────────────────────────────────────────────────────────────────────────
// La URL llega como /api/Login/Login?...  Quitamos el prefijo público (/api) para quedarnos
// con la ruta "real" que espera Wiese (/Login/Login).
$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$ruta = $uri;
if (str_starts_with($ruta, $publicPrefix)) {
    $ruta = substr($ruta, strlen($publicPrefix));
}
$ruta = '/' . ltrim($ruta, '/');

// La ruta debe empezar con alguna de las permitidas (así cubrimos query strings y subrutas).
$permitida = false;
foreach ($allowedPaths as $permitidaRuta) {
    if ($ruta === $permitidaRuta || str_starts_with($ruta, rtrim($permitidaRuta, '/') . '/')) {
        $permitida = true;
        break;
    }
}
if (! $permitida) {
    http_response_code(404);
    echo json_encode(['error' => 'Ruta no permitida: ' . $ruta]);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 5) Reenviar la petición al upstream (Wiese 7183) y devolver tal cual su respuesta
// ─────────────────────────────────────────────────────────────────────────────
$query      = $_SERVER['QUERY_STRING'] ?? '';
$urlDestino = $upstream . $ruta . ($query !== '' ? ('?' . $query) : '');
$metodo     = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$cuerpo     = file_get_contents('php://input');

// Reenviar los headers útiles (Authorization para el Bearer token de Wiese, Content-Type).
$headersReenvio = ['Accept: application/json'];
if (! empty($_SERVER['HTTP_AUTHORIZATION'])) {
    $headersReenvio[] = 'Authorization: ' . $_SERVER['HTTP_AUTHORIZATION'];
}
if (! empty($_SERVER['CONTENT_TYPE'])) {
    $headersReenvio[] = 'Content-Type: ' . $_SERVER['CONTENT_TYPE'];
}

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $urlDestino,
    CURLOPT_CUSTOMREQUEST  => $metodo,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => $headersReenvio,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT        => 60,
]);
// Solo mandar cuerpo en métodos que lo llevan.
if (in_array($metodo, ['POST', 'PUT', 'PATCH', 'DELETE'], true) && $cuerpo !== '') {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $cuerpo);
}

$respuesta = curl_exec($ch);
$httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$errCurl   = curl_error($ch);
curl_close($ch);

if ($respuesta === false) {
    http_response_code(502);
    echo json_encode(['error' => 'No se pudo contactar a Wiese', 'detalle' => $errCurl]);
    exit;
}

// Devolver la respuesta de Wiese tal cual (mismo código HTTP y cuerpo).
http_response_code($httpCode ?: 200);
echo $respuesta;
