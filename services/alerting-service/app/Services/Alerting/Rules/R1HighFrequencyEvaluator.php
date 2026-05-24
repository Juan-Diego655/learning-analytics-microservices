<?php

namespace App\Services\Alerting\Rules;

use App\Models\AlertRule;
use App\Services\Alerting\IncidentManager;
use Illuminate\Support\Facades\Redis;

/**
 * R1: Alta frecuencia anormal
 * -----------------------------------------------------------------------------
 * Detecta cuando un estudiante genera N o mas eventos en una ventana corta.
 *
 * Implementacion: Redis sorted set por estudiante.
 *   - key: alerting:r1:student:<student_id>
 *   - score: timestamp del evento
 *   - member: event_id
 *   - TTL: window_seconds
 *
 * En cada evento:
 *   1. ZADD el evento al sorted set
 *   2. ZREMRANGEBYSCORE para limpiar eventos fuera de ventana
 *   3. ZCARD para contar eventos en ventana
 *   4. Si supera threshold, disparar incidente
 */
class R1HighFrequencyEvaluator
{
    public function __construct(
        private IncidentManager $incidentManager,
    ) {}

    public function evaluate(array $event, AlertRule $rule): bool
    {
        $threshold = (int) ($rule->config['threshold'] ?? 10);
        $windowSeconds = (int) ($rule->config['window_seconds'] ?? 30);

        $studentId = $event['student_external_id'] ?? null;
        if (! $studentId) return false;

        $key = "alerting:r1:student:{$studentId}";
        $now = microtime(true);
        $windowStart = $now - $windowSeconds;

        // Agregar evento actual
        Redis::zadd($key, (int) $now, $event['event_id']);

        // Limpiar eventos fuera de ventana
        Redis::zremrangebyscore($key, '-inf', (int) $windowStart);

        // TTL del set
        Redis::expire($key, $windowSeconds + 5);

        // Contar eventos en ventana
        $count = Redis::zcard($key);

        if ($count >= $threshold) {
            // Cooldown: solo disparar una vez por estudiante cada window_seconds
            $cooldownKey = "alerting:r1:cooldown:{$studentId}";
            if (Redis::set($cooldownKey, '1', 'EX', $windowSeconds, 'NX')) {
                $this->incidentManager->trigger(
                    ruleCode: $rule->rule_code,
                    severity: $rule->severity,
                    context: [
                        'event_count_in_window' => $count,
                        'threshold' => $threshold,
                        'window_seconds' => $windowSeconds,
                        'triggering_event_id' => $event['event_id'],
                        'triggering_event_type' => $event['event_type'] ?? null,
                    ],
                    institutionId: $event['institution_id'] ?? null,
                    studentId: $studentId,
                    courseId: $event['course_external_id'] ?? null,
                );
                return true;
            }
        }

        return false;
    }
}
