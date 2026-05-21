<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Identity & Access Service
|--------------------------------------------------------------------------
| Prefix: /api (configurado en bootstrap/app.php)
*/

// Rutas públicas
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

// Rutas protegidas con JWT
Route::middleware('auth:api')->prefix('auth')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});

// Health check propio del servicio (devuelve JSON con info adicional)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'identity-access',
        'timestamp' => now()->toIso8601String(),
    ]);
});
