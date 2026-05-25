<?php

namespace App\Services\Notification\Channels;

/**
 * Log channel: escribe a stderr (visible en docker logs).
 *
 * Util para auditoria y debugging. Siempre activo, nunca falla.
 */
class LogChannel implements NotificationChannelInterface
{
    public function name(): string
    {
        return 'log';
    }

    public function recipient(): string
    {
        return 'stderr';
    }

    public function send(array $incident): bool
    {
        fwrite(STDERR, sprintf(
            "📝 [log] incident=%s rule=%s severity=%s student=%s\n",
            substr($incident['incident_id'] ?? '—', 0, 8),
            $incident['rule_code'] ?? '—',
            $incident['severity'] ?? '—',
            substr($incident['student_external_id'] ?? '—', 0, 8)
        ));

        return true;
    }
}
