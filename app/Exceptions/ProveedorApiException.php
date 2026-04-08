<?php

namespace App\Exceptions;

use Exception;

class ProveedorApiException extends Exception
{
    // Tipos de error
    const API_CAIDA = 'api_caida';
    const TIMEOUT = 'timeout';
    const AUTENTICACION_FALLIDA = 'autenticacion_fallida';
    const NO_ENCONTRADO = 'no_encontrado';
    const ERROR_SERVIDOR = 'error_servidor';
    const ERROR_VALIDACION = 'error_validacion';
    const ERROR_DESCONOCIDO = 'error_desconocido';

    private string $errorType;
    private int $httpCode;
    private array $responseData;

    private function __construct(string $message, string $errorType, int $httpCode = 0, array $responseData = [])
    {
        parent::__construct($message, $httpCode);
        $this->errorType = $errorType;
        $this->httpCode = $httpCode;
        $this->responseData = $responseData;
    }

    // Factory methods
    public static function apiCaida(string $mensaje, int $httpCode = 0): self
    {
        return new self($mensaje, self::API_CAIDA, $httpCode);
    }

    public static function timeout(string $endpoint): self
    {
        return new self("Timeout al conectar con: {$endpoint}", self::TIMEOUT, 0);
    }

    public static function autenticacionFallida(): self
    {
        return new self('Credenciales inválidas o sesión expirada', self::AUTENTICACION_FALLIDA, 401);
    }

    public static function noEncontrado(string $recurso): self
    {
        return new self("No se encontraron resultados para: {$recurso}", self::NO_ENCONTRADO, 404);
    }

    public static function errorServidor(int $httpCode, string $mensaje): self
    {
        return new self($mensaje, self::ERROR_SERVIDOR, $httpCode);
    }

    public static function errorValidacion(string $mensaje, array $data = []): self
    {
        return new self($mensaje, self::ERROR_VALIDACION, 422, $data);
    }

    public static function errorDesconocido(string $mensaje, int $httpCode = 0): self
    {
        return new self($mensaje, self::ERROR_DESCONOCIDO, $httpCode);
    }

    // Getters
    public function getErrorType(): string
    {
        return $this->errorType;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function getResponseData(): array
    {
        return $this->responseData;
    }
}
