<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ProveedorApiService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.proveedor_api.url');
    }

    // Login — obtiene token y datos del usuario
    public function login(string $codigo, string $pwd): array
    {
        $response = Http::post($this->baseUrl . '/Login/Login', [
            'codigo' => $codigo,
            'pwd'    => $pwd,
        ]);

        return $response->json() ?? [];
    }

    // Busca un proveedor por su código
    public function buscarPorCodigo(string $codigo, string $token): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get($this->baseUrl . '/ClienteProveedor/BuscarPorCodigo', [
            'codigo' => $codigo,
        ]);

        return $response->json() ?? [];
    }

    // Lista proveedores activos con paginación
    public function listarActivos(string $token, int $pagina = 1, int $porPagina = 100): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get($this->baseUrl . "/ClienteProveedor/ListarPorActivos/{$pagina}/{$porPagina}/true");

        return $response->json() ?? [];
    }

    // Lista cliente/proveedor por código
    public function listarPorCodigo(string $codigo, string $token): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get($this->baseUrl . '/ClienteProveedor/ListarClienteProvedorPorCodigo', [
            'codigo' => $codigo,
        ]);

        return $response->json() ?? [];
    }
}