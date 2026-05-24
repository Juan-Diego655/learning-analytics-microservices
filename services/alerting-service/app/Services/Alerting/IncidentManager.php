<?php

namespace App\Services\Alerting;

use App\Models\AlertIncident;
use App\Models\AlertIncidentEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * IncidentManager
 * -----------------------------------------------------------------------------
 * Coordina la maquina de estados de un AlertIncident:
 *
 *   triggered ─→ notified ─→ acknowledged ─→ resolved
 *
 * Cada transicion deja registro en alert_incident_events (append-only).
 * Es el equivalente a la "Saga" + "Event Sourcing focalizado" que
 * documenta la Fase 1 (ADR-05).
 *
 * Patron: Aggregate Root + State Machine + Event Sourcing.
 */
class IncidentManager
{
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    /**
     * Crea un incidente, lo persiste, registra el evento triggered,
     * y publica a RabbitMQ. Si la publicacion falla, el incidente
     * queda en estado 'triggered' (no avanza a 'notified').
     */
    public function trigger(string $ruleCode, string $severity, array $context, ?string $institutionId, ?string $studentId, ?string $courseId): AlertIncident
    {
        return DB::transaction(function () use ($ruleCode, $severity, $context, $institutionId, $studentId, $courseId) {
            // 1. Crear el incidente
            $incident = AlertIncident::create([
                'incident_id' => Str::uuid()->toString(),
                'rule_code' => $ruleCode,
                'institution_id' => $institutionId,
                'student_external_id' => $studentId,
                'course_external_id' => $courseId,
                'severity' => $severity,
                'status' => AlertIncident::STATUS_TRIGGERED,
                'trigger_context' => $context,
                'triggered_at' => now(),
            ]);

            // 2. Event Sourcing: append en log inmutable
            AlertIncidentEvent::create([
                'incident_id' => $incident->incident_id,
                'event_type' => 'triggered',
                'payload' => [
                    'severity' => $severity,
                    'rule_code' => $ruleCode,
                    'context' => $context,
                ],
            ]);

            // 3. Publicar a RabbitMQ (fuera de transaccion no es posible aqui,
            // pero el incident_id ya es estable; si rabbit falla, queda
            // 'triggered' para reintento manual o por scheduler)
            $published = $this->publisher->publishIncidentCreated($incident);

            if ($published) {
                $this->markNotified($incident);
            }

            Log::info("Incident triggered", [
                'incident_id' => $incident->incident_id,
                'rule_code' => $ruleCode,
                'severity' => $severity,
                'published_to_rabbitmq' => $published,
            ]);

            return $incident->fresh();
        });
    }

    /**
     * Transicion triggered → notified.
     */
    public function markNotified(AlertIncident $incident): AlertIncident
    {
        if ($incident->status !== AlertIncident::STATUS_TRIGGERED) {
            return $incident;
        }

        $incident->update([
            'status' => AlertIncident::STATUS_NOTIFIED,
            'notified_at' => now(),
        ]);

        AlertIncidentEvent::create([
            'incident_id' => $incident->incident_id,
            'event_type' => 'notified',
            'payload' => ['via' => 'rabbitmq'],
        ]);

        return $incident->fresh();
    }

    /**
     * Transicion notified → acknowledged (usuario reconoce).
     */
    public function acknowledge(AlertIncident $incident, ?string $userId = null): AlertIncident
    {
        if (! in_array($incident->status, [AlertIncident::STATUS_TRIGGERED, AlertIncident::STATUS_NOTIFIED])) {
            return $incident;
        }

        $incident->update([
            'status' => AlertIncident::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
        ]);

        AlertIncidentEvent::create([
            'incident_id' => $incident->incident_id,
            'event_type' => 'acknowledged',
            'payload' => ['by' => $userId ?? 'system'],
        ]);

        return $incident->fresh();
    }

    /**
     * Transicion → resolved.
     */
    public function resolve(AlertIncident $incident, ?string $userId = null): AlertIncident
    {
        if ($incident->status === AlertIncident::STATUS_RESOLVED) {
            return $incident;
        }

        $incident->update([
            'status' => AlertIncident::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);

        AlertIncidentEvent::create([
            'incident_id' => $incident->incident_id,
            'event_type' => 'resolved',
            'payload' => ['by' => $userId ?? 'system'],
        ]);

        return $incident->fresh();
    }
}
