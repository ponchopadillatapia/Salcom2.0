<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login-proveedor', function () {
    return view('login-proveedor');
});