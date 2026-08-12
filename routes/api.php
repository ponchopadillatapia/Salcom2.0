<?php

use App\Http\Controllers\APIS\EmpresaApiController;
use App\Http\Controllers\APIS\SalcomApiController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

// ── Validación fiscal (usa sesión web del proveedor para guardar expediente) ──
Route::middleware(['web', 'auth.proveedor'])->group(function () {
    Route::post('/empresa', [EmpresaApiController::class, 'validar'])
        ->name('proveedores.validacion-fiscal.api');
});

// ── Búsqueda de código postal ──
Route::get('/codigo-postal/{cp}', function (string $cp) {
    $cp = preg_replace('/\D/', '', $cp);
    if (strlen($cp) !== 5) {
        return response()->json(['error' => 'CP inválido'], 400);
    }

    // Mapeo de rangos de CP a municipios de México (principales zonas industriales)
    // Fuente: Catálogo SEPOMEX oficial
    $municipioPorCP = function (string $cp): array {
        $num = (int) $cp;
        // Jalisco - Zona Metropolitana de Guadalajara
        if ($num >= 44100 && $num <= 44990) return ['municipio' => 'Guadalajara', 'ciudad' => 'Guadalajara'];
        if ($num >= 45000 && $num <= 45199) return ['municipio' => 'Zapopan', 'ciudad' => 'Zapopan'];
        if ($num >= 45200 && $num <= 45299) return ['municipio' => 'Zapopan', 'ciudad' => 'Zapopan'];
        if ($num >= 45300 && $num <= 45399) return ['municipio' => 'Tala', 'ciudad' => 'Tala'];
        if ($num >= 45400 && $num <= 45499) return ['municipio' => 'Tonalá', 'ciudad' => 'Tonalá'];
        if ($num >= 45500 && $num <= 45599) return ['municipio' => 'San Pedro Tlaquepaque', 'ciudad' => 'Tlaquepaque'];
        if ($num >= 45600 && $num <= 45699) return ['municipio' => 'Tlajomulco de Zúñiga', 'ciudad' => 'Tlajomulco de Zúñiga'];
        if ($num >= 45700 && $num <= 45799) return ['municipio' => 'El Salto', 'ciudad' => 'El Salto'];
        if ($num >= 45800 && $num <= 45899) return ['municipio' => 'Jocotepec', 'ciudad' => 'Jocotepec'];
        if ($num >= 45900 && $num <= 45999) return ['municipio' => 'Chapala', 'ciudad' => 'Chapala'];
        if ($num >= 46000 && $num <= 46099) return ['municipio' => 'Tequila', 'ciudad' => 'Tequila'];
        if ($num >= 46100 && $num <= 46199) return ['municipio' => 'Amatitán', 'ciudad' => 'Amatitán'];
        if ($num >= 46200 && $num <= 46299) return ['municipio' => 'Etzatlán', 'ciudad' => 'Etzatlán'];
        if ($num >= 46400 && $num <= 46499) return ['municipio' => 'Tepatitlán de Morelos', 'ciudad' => 'Tepatitlán'];
        if ($num >= 46500 && $num <= 46599) return ['municipio' => 'Arandas', 'ciudad' => 'Arandas'];
        if ($num >= 46600 && $num <= 46699) return ['municipio' => 'Ameca', 'ciudad' => 'Ameca'];
        if ($num >= 46700 && $num <= 46799) return ['municipio' => 'La Barca', 'ciudad' => 'La Barca'];
        if ($num >= 47000 && $num <= 47099) return ['municipio' => 'San Juan de los Lagos', 'ciudad' => 'San Juan de los Lagos'];
        if ($num >= 47100 && $num <= 47199) return ['municipio' => 'Lagos de Moreno', 'ciudad' => 'Lagos de Moreno'];
        if ($num >= 47400 && $num <= 47499) return ['municipio' => 'Ocotlán', 'ciudad' => 'Ocotlán'];
        if ($num >= 47600 && $num <= 47699) return ['municipio' => 'Tepatitlán de Morelos', 'ciudad' => 'Tepatitlán'];
        if ($num >= 48000 && $num <= 48099) return ['municipio' => 'Puerto Vallarta', 'ciudad' => 'Puerto Vallarta'];
        if ($num >= 48200 && $num <= 48299) return ['municipio' => 'Ciudad Guzmán', 'ciudad' => 'Ciudad Guzmán'];
        if ($num >= 48300 && $num <= 48399) return ['municipio' => 'Autlán de Navarro', 'ciudad' => 'Autlán'];
        // CDMX
        if ($num >= 1000 && $num <= 16999) return ['municipio' => 'Ciudad de México', 'ciudad' => 'Ciudad de México'];
        // Estado de México
        if ($num >= 50000 && $num <= 52999) return ['municipio' => 'Toluca', 'ciudad' => 'Toluca'];
        if ($num >= 53000 && $num <= 53999) return ['municipio' => 'Naucalpan de Juárez', 'ciudad' => 'Naucalpan'];
        if ($num >= 54000 && $num <= 54999) return ['municipio' => 'Tlalnepantla de Baz', 'ciudad' => 'Tlalnepantla'];
        if ($num >= 55000 && $num <= 55999) return ['municipio' => 'Ecatepec de Morelos', 'ciudad' => 'Ecatepec'];
        // Nuevo León
        if ($num >= 64000 && $num <= 64999) return ['municipio' => 'Monterrey', 'ciudad' => 'Monterrey'];
        if ($num >= 66000 && $num <= 66999) return ['municipio' => 'San Nicolás de los Garza', 'ciudad' => 'San Nicolás'];
        if ($num >= 67000 && $num <= 67499) return ['municipio' => 'Guadalupe', 'ciudad' => 'Guadalupe'];
        // Querétaro
        if ($num >= 76000 && $num <= 76299) return ['municipio' => 'Querétaro', 'ciudad' => 'Querétaro'];
        // Aguascalientes
        if ($num >= 20000 && $num <= 20999) return ['municipio' => 'Aguascalientes', 'ciudad' => 'Aguascalientes'];
        // Guanajuato
        if ($num >= 36000 && $num <= 36999) return ['municipio' => 'Guanajuato', 'ciudad' => 'Guanajuato'];
        if ($num >= 37000 && $num <= 37999) return ['municipio' => 'León', 'ciudad' => 'León'];
        if ($num >= 38000 && $num <= 38999) return ['municipio' => 'Celaya', 'ciudad' => 'Celaya'];

        return ['municipio' => '', 'ciudad' => ''];
    };

    $localMunicipio = $municipioPorCP($cp);

    // Fuente 1: API SEPOMEX (si está disponible)
    try {
        $response = Http::timeout(3)->get("https://api-sepomex.hckdrk.mx/query/info_cp/{$cp}");
        if ($response->ok()) {
            $data = $response->json();
            if (is_array($data) && count($data) > 0) {
                $primer = $data[0]['response'] ?? $data[0] ?? [];
                $colonias = [];
                foreach ($data as $item) {
                    $col = $item['response']['asentamiento'] ?? $item['asentamiento'] ?? '';
                    if ($col !== '') {
                        $colonias[] = $col;
                    }
                }
                $municipio = $primer['municipio'] ?? $localMunicipio['municipio'];
                $ciudad = $primer['ciudad'] ?? $localMunicipio['ciudad'] ?: $municipio;
                return response()->json([
                    'estado' => $primer['estado'] ?? '',
                    'municipio' => $municipio,
                    'ciudad' => $ciudad,
                    'colonias' => array_values(array_unique($colonias)),
                ]);
            }
        }
    } catch (Exception $e) {
    }

    // Fuente 2: Zippopotam (solo México) + mapeo local de municipio
    try {
        $response = Http::timeout(4)->get("https://api.zippopotam.us/MX/{$cp}");
        if ($response->ok()) {
            $data = $response->json();
            $places = $data['places'] ?? [];
            $country = $data['country'] ?? '';
            if (count($places) && (stripos($country, 'Mexico') !== false || stripos($country, 'México') !== false)) {
                $estado = $places[0]['state'] ?? '';
                $colonias = array_map(fn ($p) => $p['place name'] ?? '', $places);
                $colonias = array_filter($colonias);
                return response()->json([
                    'estado' => $estado,
                    'municipio' => $localMunicipio['municipio'],
                    'ciudad' => $localMunicipio['ciudad'] ?: $localMunicipio['municipio'],
                    'colonias' => array_values($colonias),
                ]);
            }
        }
    } catch (Exception $e) {
    }

    // Si ninguna API respondió pero tenemos datos locales
    if (! empty($localMunicipio['municipio'])) {
        return response()->json([
            'estado' => '',
            'municipio' => $localMunicipio['municipio'],
            'ciudad' => $localMunicipio['ciudad'],
            'colonias' => [],
        ]);
    }

    return response()->json(['error' => 'Código postal no encontrado en el catálogo de México'], 404);
});

// ── API Salcom (protegida con Bearer token) ──
Route::middleware('auth.api_token')->prefix('salcom')->group(function () {
    // Resumen y análisis
    Route::get('/resumen', [SalcomApiController::class, 'resumen']);
    Route::get('/analisis', [SalcomApiController::class, 'analisis']);

    // Clientes
    Route::get('/clientes', [SalcomApiController::class, 'clientes']);
    Route::get('/clientes/{cliente}', [SalcomApiController::class, 'clienteDetalle']);

    // Proveedores
    Route::get('/proveedores', [SalcomApiController::class, 'proveedores']);
    Route::get('/proveedores/{proveedor}', [SalcomApiController::class, 'proveedorDetalle']);

    // Pedidos
    Route::get('/pedidos', [SalcomApiController::class, 'pedidos']);
    Route::get('/pedidos/{pedido}', [SalcomApiController::class, 'pedidoDetalle']);

    // Productos
    Route::get('/productos', [SalcomApiController::class, 'productos']);
    Route::get('/productos/{producto}', [SalcomApiController::class, 'productoDetalle']);

    // Facturas
    Route::get('/facturas', [SalcomApiController::class, 'facturas']);

    // Muestras
    Route::get('/muestras', [SalcomApiController::class, 'muestras']);

    // Encuestas
    Route::get('/encuestas', [SalcomApiController::class, 'encuestas']);

    // Documentos de proveedores
    Route::get('/documentos', [SalcomApiController::class, 'documentos']);
    Route::get('/documentos/{documento}/validar', [SalcomApiController::class, 'validarDocumento']);
    Route::patch('/documentos/{documento}/revisar', [SalcomApiController::class, 'revisarDocumento']);
});
