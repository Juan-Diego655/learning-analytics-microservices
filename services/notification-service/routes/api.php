<?php

use App\Http\Controllers\NotificationsController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'notification-dispatcher',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::prefix('notifications')->group(function () {
    Route::get('/recent', [NotificationsController::class, 'recent']);
    Route::get('/stats', [NotificationsController::class, 'stats']);
    Route::get('/by-incident/{incidentId}', [NotificationsController::class, 'byIncident']);
});
