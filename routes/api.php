<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\APIS\EmpresaApiController;

Route::post('/empresa', [EmpresaApiController::class, 'validar']);