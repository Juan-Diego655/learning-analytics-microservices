<?php

namespace App\Http\Controllers;

use App\Models\NotificationSent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * NotificationsController
 *
 * Expone los datos del Notification Dispatcher: ultimas notificaciones
 * enviadas y estadisticas agregadas. Util para que el frontend o auditores
 * vean que esta llegando del consumer.
 */
class NotificationsController extends Controller
{
    /**
     * GET /api/notifications/recent
     *
     * Devuelve las ultimas N notificaciones enviadas (por defecto 20).
     * Filtros opcionales: ?channel=email&status=sent
     */
    public function recent(Request $request): JsonResponse
    {
        $query = NotificationSent::query()->orderByDesc('sent_at');

        if ($channel = $request->query('channel')) {
            $query->where('channel', $channel);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $limit = min((int) $request->query('limit', 20), 100);
        $notifications = $query->limit($limit)->get();

        return response()->json([
            'total' => $notifications->count(),
            'notifications' => $notifications,
        ]);
    }

    /**
     * GET /api/notifications/stats
     *
     * Devuelve estadisticas agregadas: total, por canal, por status.
     */
    public function stats(): JsonResponse
    {
        $total = NotificationSent::count();

        $byChannel = NotificationSent::query()
            ->selectRaw('channel, count(*) as count')
            ->groupBy('channel')
            ->pluck('count', 'channel');

        $byStatus = NotificationSent::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $byChannelAndStatus = NotificationSent::query()
            ->selectRaw('channel, status, count(*) as count')
            ->groupBy('channel', 'status')
            ->get()
            ->groupBy('channel')
            ->map(function ($items) {
                return $items->pluck('count', 'status');
            });

        $lastSent = NotificationSent::query()
            ->orderByDesc('sent_at')
            ->value('sent_at');

        return response()->json([
            'total' => $total,
            'by_channel' => $byChannel,
            'by_status' => $byStatus,
            'by_channel_and_status' => $byChannelAndStatus,
            'last_sent_at' => $lastSent,
        ]);
    }

    /**
     * GET /api/notifications/by-incident/{incidentId}
     *
     * Devuelve todas las notificaciones enviadas para un incidente.
     */
    public function byIncident(string $incidentId): JsonResponse
    {
        $notifications = NotificationSent::query()
            ->where('incident_id', $incidentId)
            ->orderBy('channel')
            ->get();

        return response()->json([
            'incident_id' => $incidentId,
            'total' => $notifications->count(),
            'notifications' => $notifications,
        ]);
    }
}
