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

    public function login(string $codigo, string $pwd): array
    {
        $response = Http::post($this->baseUrl . '/Login/Login', [
            'codigo' => $codigo,
            'pwd'    => $pwd,
        ]);

        return $response->json();
    }
}