<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $permitidas = config('services.salcom_api.allowed_ips', []);
        $ip = $this->normalizarIp((string) $request->ip());

        if (! is_array($permitidas) || ! $this->ipPermitida($ip, $permitidas)) {
            Log::warning('Consulta API Salcom bloqueada por IP', ['ip' => $ip]);

            return response()->json(['error' => 'No autorizado'], 403);
        }

        $token = $request->bearerToken();
        $expected = config('services.salcom_api.token');

        if (! $expected || $token !== $expected) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        return $next($request);
    }

    private function ipPermitida(string $ip, array $permitidas): bool
    {
        foreach ($permitidas as $permitida) {
            if ($this->normalizarIp((string) $permitida) === $ip) {
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
}
