<?php

use App\Http\Controllers\GatewayAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Gateway — Routes
|--------------------------------------------------------------------------
| Entry point único del sistema. Las rutas se agrupan por:
|  - Públicas (login, health)
|  - Protegidas con JWT (me, logout, y futuros proxies a otros servicios)
*/

// =============================================================================
// Públicas
// =============================================================================
Route::prefix('auth')->group(function () {
    Route::post('login', [GatewayAuthController::class, 'login']);
});

// Health check del Gateway
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'api-gateway',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// =============================================================================
// Protegidas con JWT
// =============================================================================
Route::middleware('jwt.verify')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('me', [GatewayAuthController::class, 'me']);
        Route::post('logout', [GatewayAuthController::class, 'logout']);
    });

    // Aquí en D3-D5 agregaremos proxies a los otros servicios:
    // Route::any('events/{any}', [ProxyController::class, 'forwardToIngestion']);
    // Route::any('metrics/{any}', [ProxyController::class, 'forwardToTelemetry']);
    // Route::any('incidents/{any}', [ProxyController::class, 'forwardToAlerting']);
});
