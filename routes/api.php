<?php

use App\Http\Controllers\Api\AccountActivationController;
use App\Http\Controllers\Api\Admin\EmployeeController;
use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'data' => [
        'status' => 'ok',
        'service' => 'controle-acesso-api-laravel',
    ],
]));

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/activate', [AccountActivationController::class, 'store']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me'])->middleware('active.account');
});

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'central.administrator'])
    ->group(function (): void {
        Route::get('/permissions', [PermissionController::class, 'index']);
        Route::get('/employees', [EmployeeController::class, 'index']);
        Route::post('/employees', [EmployeeController::class, 'store']);
        Route::get('/employees/{employee}', [EmployeeController::class, 'show']);
        Route::put('/employees/{employee}', [EmployeeController::class, 'update']);
        Route::patch('/employees/{employee}/status', [EmployeeController::class, 'updateStatus']);
        Route::post('/employees/{employee}/resend-invitation', [EmployeeController::class, 'resendInvitation']);
    });
