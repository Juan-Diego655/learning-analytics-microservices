<?php

use App\Http\Controllers\EventIngestionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Event Ingestion Service
|--------------------------------------------------------------------------
| Patrón: Anti-Corruption Layer.
| Cada LMS tiene su path propio para que la URL revele el origen.
*/

// Health (también lo sirve nginx en /health, pero también lo exponemos aquí)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'event-ingestion',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Ingesta de eventos por LMS
Route::post('/events/{lms}', [EventIngestionController::class, 'ingest'])
    ->whereIn('lms', ['moodle', 'canvas', 'openedx']);

// Lista los últimos eventos (validación manual)
Route::get('/events/recent', [EventIngestionController::class, 'recent']);
