<?php

use App\Http\Controllers\MetricsController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'telemetry-processing',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::prefix('metrics')->group(function () {
    Route::get('windows', [MetricsController::class, 'recent']);
    Route::get('summary', [MetricsController::class, 'summary']);
});
