<?php

use App\Http\Controllers\Api\AccountActivationController;
use App\Http\Controllers\Api\Admin\EmployeeController;
use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\Admin\SchoolClassController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GuardianController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\SchoolClassOptionController;
use App\Http\Controllers\Api\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'data' => [
        'status' => 'ok',
        'service' => 'controle-acesso-api-laravel',
    ],
]));

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/activate', [AccountActivationController::class, 'store']);
Route::post('/auth/forgot-password', [PasswordResetController::class, 'sendLink']);
Route::post('/auth/reset-password', [PasswordResetController::class, 'reset']);

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
        Route::get('/school-classes', [SchoolClassController::class, 'index']);
        Route::post('/school-classes', [SchoolClassController::class, 'store']);
        Route::put('/school-classes/{schoolClass}', [SchoolClassController::class, 'update']);
        Route::patch('/school-classes/{schoolClass}/status', [SchoolClassController::class, 'updateStatus']);
    });

Route::get('/school-classes/options', [SchoolClassOptionController::class, 'index'])
    ->middleware(['auth:sanctum', 'active.account', 'permission:students.create']);

Route::middleware(['auth:sanctum', 'active.account'])->group(function (): void {
    Route::get('/guardians', [GuardianController::class, 'index'])
        ->middleware('permission:students.create');
    Route::put('/guardians/{guardian}', [GuardianController::class, 'update'])
        ->middleware('permission:students.update');
    Route::post('/guardians/{guardian}/resend-invitation', [GuardianController::class, 'resendInvitation'])
        ->middleware('permission:students.create');

    Route::get('/students', [StudentController::class, 'index'])
        ->middleware('permission:students.view');
    Route::post('/students', [StudentController::class, 'store'])
        ->middleware('permission:students.create');
    Route::get('/students/{student}', [StudentController::class, 'show'])
        ->middleware('permission:students.view');
    Route::put('/students/{student}', [StudentController::class, 'update'])
        ->middleware('permission:students.update');
    Route::patch('/students/{student}/status', [StudentController::class, 'updateStatus'])
        ->middleware('permission:students.change_status');
});
