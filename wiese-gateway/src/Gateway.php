<?php

declare(strict_types=1);

namespace WieseGateway;

/**
 * Puerta entre SiteGround y la API de Wiese (puerto 7183).
 * El 7183 no sale a internet: solo esta puerta, con IP y token, puede consultarlo.
 */
final class Gateway
{
    public const HEADER = 'X-Salcom-Gateway';

    /** Rutas que Salcom usa. Cualquier otra se rechaza. */
    public const RUTAS = [
        '/Login/Login',
        '/ClienteProveedor/BuscarPorCodigo',
        '/ClienteProveedor/BuscarPorRFC',
        '/ClienteProveedor/ListarClienteProvedorPorCodigo',
        '/ClienteProveedor/ListarProveedorWeb',
        '/Documento/ListaDocumentosOCPorProveedor',
        '/DatoDocumentoProveedor/ListarDocumentosRFC',
    ];

    /**
     * @param  list<string>  $allowedIps
     * @param  callable|null  $forwarder  function (string $method, string $url, array $headers, string $body): array
     */
    public function __construct(
        private string $upstream,
        private string $token,
        private array $allowedIps,
        private string $publicPrefix = '/api',
        private int $timeout = 60,
        private $forwarder = null,
    ) {
        $this->upstream = rtrim($this->upstream, '/');
        $this->publicPrefix = $this->normalizarPrefijo($publicPrefix);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(array $config, ?callable $forwarder = null): self
    {
        $ips = $config['allowed_ips'] ?? ['35.215.67.229'];

        return new self(
            (string) ($config['upstream'] ?? 'http://172.16.1.250:7183/api'),
            (string) ($config['token'] ?? ''),
            is_array($ips) ? array_values(array_map('strval', $ips)) : [],
            (string) ($config['public_prefix'] ?? '/api'),
            (int) ($config['timeout'] ?? 60),
            $forwarder,
        );
    }

    /**
     * @param  array<string, string>  $headers
     * @return array{status:int, body:string, content_type:string}
     */
    public function handle(string $method, string $uri, array $headers, string $body, string $ip): array
    {
        if ($this->upstream === '' || $this->token === '' || $this->allowedIps === []) {
            return $this->error(500, 'Puerta sin configurar');
        }

        if (! $this->ipPermitida($ip)) {
            return $this->error(403, 'No autorizado');
        }

        $recibido = $this->header($headers, self::HEADER);
        if ($recibido === '' || ! hash_equals($this->token, $recibido)) {
            return $this->error(401, 'No autorizado');
        }

        if (strlen($body) > 1_048_576) {
            return $this->error(413, 'Cuerpo demasiado grande');
        }

        $method = strtoupper($method);
        if (! in_array($method, ['GET', 'POST'], true)) {
            return $this->error(405, 'Método no permitido');
        }

        $ruta = $this->rutaPermitida($uri);
        if ($ruta === null) {
            return $this->error(404, 'No encontrado');
        }

        $url = $this->upstream.$ruta['path'];
        if ($ruta['query'] !== '') {
            $url .= '?'.$ruta['query'];
        }

        $forwardHeaders = ['Accept: application/json'];
        $auth = $this->header($headers, 'Authorization');
        if ($auth !== '') {
            $forwardHeaders[] = 'Authorization: '.$auth;
        }
        if ($method === 'POST') {
            $contentType = $this->header($headers, 'Content-Type');
            $forwardHeaders[] = 'Content-Type: '.($contentType !== '' ? $contentType : 'application/json');
        }

        $payload = $method === 'POST' ? $body : '';

        if ($this->forwarder !== null) {
            $respuesta = ($this->forwarder)($method, $url, $forwardHeaders, $payload);

            return [
                'status' => (int) ($respuesta['status'] ?? 502),
                'body' => (string) ($respuesta['body'] ?? ''),
                'content_type' => (string) ($respuesta['content_type'] ?? 'application/json'),
            ];
        }

        return $this->forwardConCurl($method, $url, $forwardHeaders, $payload);
    }

    /**
     * @return array{path:string, query:string}|null
     */
    private function rutaPermitida(string $uri): ?array
    {
        $partes = parse_url($uri);
        if (! is_array($partes)) {
            return null;
        }

        $path = (string) ($partes['path'] ?? '');
        $query = (string) ($partes['query'] ?? '');

        if ($path === '' || str_contains($path, '..') || str_contains($path, '\\') || str_contains($path, "\0")) {
            return null;
        }
        if (str_contains($query, "\n") || str_contains($query, "\r")) {
            return null;
        }

        $coincidePrefijo = $this->publicPrefix !== ''
            && ($path === $this->publicPrefix || str_starts_with($path, $this->publicPrefix.'/'));
        if ($coincidePrefijo) {
            $path = substr($path, strlen($this->publicPrefix));
        }

        if ($path === '' || $path[0] !== '/') {
            $path = '/'.$path;
        }

        if (! in_array($path, self::RUTAS, true)) {
            return null;
        }

        return ['path' => $path, 'query' => $query];
    }

    private function ipPermitida(string $ip): bool
    {
        $ip = $this->normalizarIp($ip);
        foreach ($this->allowedIps as $permitida) {
            if ($this->normalizarIp($permitida) === $ip) {
                return true;
            }
        }

        return false;
    }

    private function normalizarIp(string $ip): string
    {
        $ip = trim($ip);
        if (str_starts_with(strtolower($ip), '::ffff:')) {
            $ip = substr($ip, 7);
        }

        return $ip;
    }

    private function normalizarPrefijo(string $prefijo): string
    {
        $prefijo = '/'.trim($prefijo, '/');

        return $prefijo === '/' ? '' : $prefijo;
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function header(array $headers, string $nombre): string
    {
        foreach ($headers as $clave => $valor) {
            if (strcasecmp((string) $clave, $nombre) === 0) {
                return trim((string) $valor);
            }
        }

        return '';
    }

    /**
     * @param  list<string>  $headers
     * @return array{status:int, body:string, content_type:string}
     */
    private function forwardConCurl(string $method, string $url, array $headers, string $body): array
    {
        if (! function_exists('curl_init')) {
            return $this->error(500, 'PHP sin cURL');
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return $this->error(502, 'No se pudo abrir la conexión con Wiese');
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            curl_close($ch);

            return $this->error(502, 'No se pudo conectar con el puerto 7183');
        }

        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $rawHeaders = substr($raw, 0, $headerSize);
        $respuesta = substr($raw, $headerSize);
        $contentType = 'application/json';
        if (preg_match('/^Content-Type:\s*([^\r\n;]+)/mi', $rawHeaders, $coincide)) {
            $contentType = trim($coincide[1]);
        }

        return [
            'status' => $status > 0 ? $status : 502,
            'body' => $respuesta,
            'content_type' => $contentType,
        ];
    }

    /**
     * @return array{status:int, body:string, content_type:string}
     */
    private function error(int $status, string $mensaje): array
    {
        return [
            'status' => $status,
            'body' => (string) json_encode(['error' => $mensaje], JSON_UNESCAPED_UNICODE),
            'content_type' => 'application/json',
        ];
    }
}
