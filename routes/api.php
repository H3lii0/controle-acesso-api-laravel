<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'data' => [
        'status' => 'ok',
        'service' => 'controle-acesso-api-laravel',
    ],
]));
