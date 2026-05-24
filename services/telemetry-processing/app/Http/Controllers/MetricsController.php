<?php

namespace App\Http\Controllers;

use App\Models\EventProcessed;
use App\Models\MetricWindow5min;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetricsController extends Controller
{
    /**
     * GET /api/metrics/windows
     * Lista las últimas N ventanas agregadas.
     */
    public function recent(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 20), 100);

        $windows = MetricWindow5min::orderByDesc('window_start')
            ->limit($limit)
            ->get(['id', 'window_start', 'window_end', 'institution_id',
                   'course_external_id', 'event_type', 'event_count', 'unique_students']);

        return response()->json([
            'total_windows' => MetricWindow5min::count(),
            'total_events_processed' => EventProcessed::count(),
            'windows' => $windows,
        ]);
    }

    /**
     * GET /api/metrics/summary
     * Resumen rápido para dashboard.
     */
    public function summary(): JsonResponse
    {
        return response()->json([
            'events_processed_total' => EventProcessed::count(),
            'events_by_lms' => EventProcessed::selectRaw('lms_source, COUNT(*) as total')
                ->groupBy('lms_source')->pluck('total', 'lms_source'),
            'events_by_type' => EventProcessed::selectRaw('event_type, COUNT(*) as total')
                ->groupBy('event_type')->orderByDesc('total')->pluck('total', 'event_type'),
            'active_windows' => MetricWindow5min::count(),
            'last_window' => MetricWindow5min::orderByDesc('window_start')->first(),
        ]);
    }
}
