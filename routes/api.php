<?php

use App\Http\Controllers\APIS\EmpresaApiController;
Route::post('/validar-documentos', [EmpresaApiController::class, 'validar']);