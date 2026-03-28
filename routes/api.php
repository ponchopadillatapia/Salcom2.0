<?php

use App\Http\Controllers\APIS\EmpresaApiController;
Route::get('/empresa', [EmpresaController::class, 'form']);
Route::post('/empresa', [EmpresaController::class, 'guardar']);