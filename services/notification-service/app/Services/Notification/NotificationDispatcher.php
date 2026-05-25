<?php

namespace App\Services\Notification;

use App\Models\NotificationSent;
use App\Services\Notification\Channels\NotificationChannelInterface;
use App\Services\Notification\Channels\EmailMockChannel;
use App\Services\Notification\Channels\SlackMockChannel;
use App\Services\Notification\Channels\LogChannel;
use Illuminate\Support\Facades\DB;

/**
 * NotificationDispatcher
 *
 * Orquesta el envio de notificaciones a multiples canales.
 * Cada canal se intenta de forma independiente (try/catch aislado),
 * de modo que un fallo en email no impide que slack o log se ejecuten.
 *
 * Cada intento se persiste en la tabla notifications_sent con su status,
 * generando una bitacora completa para auditoria.
 */
class NotificationDispatcher
{
    /** @var NotificationChannelInterface[] */
    private array $channels;

    public function __construct(?array $channels = null)
    {
        $this->channels = $channels ?? [
            new EmailMockChannel(),
            new SlackMockChannel(),
            new LogChannel(),
        ];
    }

    /**
     * Despacha la notificacion a todos los canales configurados.
     *
     * @param array $incident Payload completo del incidente
     * @return array Resumen: ['total' => N, 'sent' => N, 'failed' => N]
     */
    public function dispatch(array $incident): array
    {
        $sent = 0;
        $failed = 0;

        foreach ($this->channels as $channel) {
            $status = NotificationSent::STATUS_SENT;
            $errorMessage = null;

            try {
                $channel->send($incident);
                $sent++;
            } catch (\Throwable $e) {
                $status = NotificationSent::STATUS_FAILED;
                $errorMessage = $e->getMessage();
                $failed++;
            }

            // Persistir el intento (sea exito o fallo) en notifications_sent
            try {
                NotificationSent::create([
                    'incident_id' => $incident['incident_id'] ?? null,
                    'rule_code' => $incident['rule_code'] ?? 'UNKNOWN',
                    'severity' => $incident['severity'] ?? 'info',
                    'channel' => $channel->name(),
                    'recipient' => $channel->recipient(),
                    'status' => $status,
                    'payload' => $incident,
                    'error_message' => $errorMessage,
                    'sent_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // Si no podemos persistir, al menos lo dejamos en logs
                fwrite(STDERR, sprintf(
                    "[dispatcher] No se pudo persistir notification: %s\n",
                    $e->getMessage()
                ));
            }
        }

        return [
            'total' => count($this->channels),
            'sent' => $sent,
            'failed' => $failed,
        ];
    }
}
