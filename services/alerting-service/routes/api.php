<?php

use App\Http\Controllers\IncidentsController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'alerting',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Reglas activas
Route::get('/rules', [IncidentsController::class, 'rules']);

// Incidentes
Route::prefix('incidents')->group(function () {
    Route::get('/', [IncidentsController::class, 'index']);
    Route::get('/{id}', [IncidentsController::class, 'show']);
    Route::post('/{id}/acknowledge', [IncidentsController::class, 'acknowledge']);
    Route::post('/{id}/resolve', [IncidentsController::class, 'resolve']);
});
