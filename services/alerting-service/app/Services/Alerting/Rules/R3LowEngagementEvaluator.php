<?php

namespace App\Services\Alerting\Rules;

use App\Models\AlertRule;
use App\Services\Alerting\IncidentManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * R3: Engagement bajo en curso activo
 * -----------------------------------------------------------------------------
 * Evaluacion BATCH (no stream): se ejecuta periodicamente desde un schedule.
 *
 * Logica:
 *   1. Consultar al servicio Telemetry (HTTP GET /api/metrics/summary)
 *      por las ventanas activas mas recientes
 *   2. Para cada curso con actividad agregada:
 *      a) Identificar estudiantes con <min_events eventos en la ventana
 *      b) Si el curso tiene actividad agregada >= min_events_course_level,
 *         alertar por los estudiantes con engagement bajo
 *
 * Patron: Service-to-service synchronous call para batch enrichment.
 * Esto demuestra integracion HTTP entre microservicios ademas de Kafka.
 */
class R3LowEngagementEvaluator
{
    public function __construct(
        private IncidentManager $incidentManager,
    ) {}

    public function evaluate(AlertRule $rule): int
    {
        $minEvents = (int) ($rule->config['min_events'] ?? 2);

        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get('http://la-telemetry/api/metrics/windows?limit=50');

            if (! $response->ok()) {
                Log::warning('R3: telemetry returned non-200', ['status' => $response->status()]);
                return 0;
            }

            $data = $response->json();
            $windows = $data['windows'] ?? [];
        } catch (\Throwable $e) {
            Log::error('R3: cannot reach telemetry', ['error' => $e->getMessage()]);
            return 0;
        }

        $triggered = 0;

        // Agrupar ventanas por (institution, course, window_start)
        $byCourseWindow = [];
        foreach ($windows as $w) {
            $key = "{$w['institution_id']}|{$w['course_external_id']}|{$w['window_start']}";
            $byCourseWindow[$key][] = $w;
        }

        // Para cada combinacion, detectar bajo engagement
        foreach ($byCourseWindow as $key => $courseWindows) {
            $totalEventsCurso = array_sum(array_column($courseWindows, 'event_count'));
            $totalUniqueStudents = array_sum(array_column($courseWindows, 'unique_students'));

            // Heuristica simple: si el curso tiene actividad alta total
            // pero el promedio de eventos por estudiante es bajo
            if ($totalUniqueStudents === 0) continue;

            $avgEventsPerStudent = $totalEventsCurso / $totalUniqueStudents;

            if ($avgEventsPerStudent < $minEvents && $totalEventsCurso >= 5) {
                [$institutionId, $courseId, $windowStart] = explode('|', $key);

                // Cooldown por (curso, ventana) para no duplicar
                $cooldownKey = "alerting:r3:cooldown:{$institutionId}:{$courseId}:{$windowStart}";
                if (Redis::set($cooldownKey, '1', 'EX', 3600, 'NX')) {
                    $this->incidentManager->trigger(
                        ruleCode: $rule->rule_code,
                        severity: $rule->severity,
                        context: [
                            'window_start' => $windowStart,
                            'total_events_in_window' => $totalEventsCurso,
                            'unique_students' => $totalUniqueStudents,
                            'avg_events_per_student' => round($avgEventsPerStudent, 2),
                            'min_events_threshold' => $minEvents,
                        ],
                        institutionId: $institutionId,
                        studentId: null,
                        courseId: $courseId,
                    );
                    $triggered++;
                }
            }
        }

        Log::info("R3 batch evaluation completed", ['incidents_triggered' => $triggered]);
        return $triggered;
    }
}
