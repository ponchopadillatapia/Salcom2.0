<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        $expected = config('services.salcom_api.token');

        if (!$expected || $token !== $expected) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        return $next($request);
    }
}
