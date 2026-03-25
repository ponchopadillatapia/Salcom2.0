<?php

use App\Http\Controllers\APIS\EmpresaApiController;
//Route::get('/validar-documentos', [EmpresaApiController::class, 'validar']);
Route::post('/empresa', [EmpresaController::class, 'guardar']);