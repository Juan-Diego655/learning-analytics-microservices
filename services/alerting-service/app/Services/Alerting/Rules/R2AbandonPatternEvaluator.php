<?php

namespace App\Services\Alerting\Rules;

use App\Models\AlertRule;
use App\Services\Alerting\IncidentManager;
use Illuminate\Support\Facades\Redis;

/**
 * R2: Patron de abandono de tareas
 * -----------------------------------------------------------------------------
 * Detecta estudiantes que registran 3+ eventos de actividad (forum_post,
 * quiz_attempted, course_viewed) en un curso, sin entregar ningun
 * assignment_submitted en una ventana de 10 min.
 *
 * Implementacion: 2 contadores Redis por (estudiante, curso):
 *   - alerting:r2:activity:<student>:<course>  → contador de eventos tracked
 *   - alerting:r2:submitted:<student>:<course> → flag si entrego (TTL window)
 *
 * Si llega un assignment_submitted → set flag (resetea posibilidad de alerta).
 * Si llega un evento tracked → incrementa contador; si >= threshold y NO hay
 * flag de submitted → disparar incidente.
 */
class R2AbandonPatternEvaluator
{
    public function __construct(
        private IncidentManager $incidentManager,
    ) {}

    public function evaluate(array $event, AlertRule $rule): bool
    {
        $threshold = (int) ($rule->config['threshold'] ?? 3);
        $windowSeconds = (int) ($rule->config['window_seconds'] ?? 600);
        $trackedEvents = $rule->config['tracked_events'] ?? ['forum_post', 'quiz_attempted', 'course_viewed'];
        $expectedEvent = $rule->config['expected_event'] ?? 'assignment_submitted';

        $studentId = $event['student_external_id'] ?? null;
        $courseId = $event['course_external_id'] ?? null;
        $eventType = $event['event_type'] ?? null;

        if (! $studentId || ! $courseId || ! $eventType) return false;

        $activityKey = "alerting:r2:activity:{$studentId}:{$courseId}";
        $submittedKey = "alerting:r2:submitted:{$studentId}:{$courseId}";
        $cooldownKey = "alerting:r2:cooldown:{$studentId}:{$courseId}";

        // Caso A: llega el "expected" → resetear estado
        if ($eventType === $expectedEvent) {
            Redis::set($submittedKey, '1', 'EX', $windowSeconds);
            Redis::del($activityKey);
            return false;
        }

        // Caso B: no es un evento "tracked" → ignorar
        if (! in_array($eventType, $trackedEvents, true)) {
            return false;
        }

        // Si ya entregó en la ventana, no alertar
        if (Redis::exists($submittedKey)) {
            return false;
        }

        // Incrementar contador de actividad
        $count = Redis::incr($activityKey);
        Redis::expire($activityKey, $windowSeconds);

        if ($count >= $threshold) {
            // Cooldown para no spam-alertar
            if (Redis::set($cooldownKey, '1', 'EX', $windowSeconds, 'NX')) {
                $this->incidentManager->trigger(
                    ruleCode: $rule->rule_code,
                    severity: $rule->severity,
                    context: [
                        'activity_count' => $count,
                        'threshold' => $threshold,
                        'window_seconds' => $windowSeconds,
                        'last_event_type' => $eventType,
                        'expected_event' => $expectedEvent,
                    ],
                    institutionId: $event['institution_id'] ?? null,
                    studentId: $studentId,
                    courseId: $courseId,
                );
                return true;
            }
        }

        return false;
    }
}
