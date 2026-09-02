<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EstruturaEscolarController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'data' => [
        'status' => 'ok',
        'service' => 'controle-acesso-api-laravel',
    ],
]));

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/estrutura-escolar', [EstruturaEscolarController::class, 'index']);
});
