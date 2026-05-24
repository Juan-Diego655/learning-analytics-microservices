<?php

namespace App\Http\Controllers;

use App\Models\RawEvent;
use App\Services\Ingestion\Adapters\CanvasAdapter;
use App\Services\Ingestion\Adapters\LmsAdapterInterface;
use App\Services\Ingestion\Adapters\MoodleAdapter;
use App\Services\Ingestion\Adapters\OpenedxAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class EventIngestionController extends Controller
{
    /**
     * POST /api/events/{lms}
     * Recibe un evento crudo de un LMS externo y lo procesa:
     *  1. Selecciona el adaptador correcto (ACL)
     *  2. Normaliza al esquema canónico
     *  3. Persiste en raw_events
     *  4. Publica a Kafka topic lms.events.raw
     *
     * Patrón: Anti-Corruption Layer (Evans, DDD Cap. 14)
     */
    public function ingest(Request $request, string $lms): JsonResponse
    {
        // 1. Selección del adaptador
        $adapter = $this->resolveAdapter($lms);
        if (! $adapter) {
            return response()->json([
                'error' => 'unsupported_lms',
                'message' => "LMS '$lms' no soportado. Disponibles: moodle, canvas, openedx.",
            ], 400);
        }

        // 2. Validación mínima del payload (debe ser JSON con algún contenido)
        $rawPayload = $request->all();
        if (empty($rawPayload)) {
            return response()->json([
                'error' => 'empty_payload',
                'message' => 'El payload del evento no puede estar vacío.',
            ], 400);
        }

        $institutionId = $request->header('X-Institution-Id')
            ?? $request->input('_institution_id')
            ?? '00000000-0000-0000-0000-000000000000';

        // 3. Normalización al esquema canónico (Anti-Corruption Layer)
        try {
            $canonical = $adapter->toCanonical($rawPayload, $institutionId);
        } catch (\Throwable $e) {
            Log::error('ACL normalization failed', [
                'lms' => $lms,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'normalization_failed',
                'message' => 'No se pudo normalizar el evento al esquema canónico.',
            ], 422);
        }

        // 4. Persistencia
        $event = RawEvent::create([
            'event_id' => Str::uuid()->toString(),
            'occurred_at' => $canonical['occurred_at'],
            'lms_source' => $canonical['lms_source'],
            'institution_id' => $canonical['institution_id'],
            'student_external_id' => $canonical['student_external_id'],
            'course_external_id' => $canonical['course_external_id'],
            'event_type' => $canonical['event_type'],
            'raw_payload' => $rawPayload,
            'canonical_payload' => $canonical,
            'published_to_kafka' => false,
        ]);

        // 5. Publicación a Kafka (asíncrono, no bloqueamos la respuesta si falla)
        $publishedOk = $this->publishToKafka($event);
        if ($publishedOk) {
            $event->update(['published_to_kafka' => true]);
        }

        return response()->json([
            'status' => 'accepted',
            'event_id' => $event->event_id,
            'lms_source' => $event->lms_source,
            'event_type' => $event->event_type,
            'published_to_kafka' => $publishedOk,
            'canonical' => $canonical,
        ], 202);
    }

    /**
     * Selecciona el adaptador correcto según el path param.
     */
    private function resolveAdapter(string $lms): ?LmsAdapterInterface
    {
        return match (strtolower($lms)) {
            'moodle' => new MoodleAdapter(),
            'canvas' => new CanvasAdapter(),
            'openedx' => new OpenedxAdapter(),
            default => null,
        };
    }

    /**
     * Publica el evento canónico al topic lms.events.raw de Kafka.
     * La key del mensaje es institution_id para garantizar ordering por IES.
     */
    private function publishToKafka(RawEvent $event): bool
    {
        try {
            $message = new Message(
    topicName: config('kafka.topics.events_raw', 'lms.events.raw'),
    partition: RD_KAFKA_PARTITION_UA,
    headers: [
        'lms_source' => $event->lms_source,
        'event_type' => $event->event_type,
        'schema_version' => 'v1',
    ],
    body: [
        'event_id' => $event->event_id,
        'occurred_at' => $event->occurred_at?->toIso8601String(),
        'lms_source' => $event->lms_source,
        'institution_id' => $event->institution_id,
        'student_external_id' => $event->student_external_id,
        'course_external_id' => $event->course_external_id,
        'event_type' => $event->event_type,
        'raw_payload' => $event->raw_payload,
    ],
    key: $event->institution_id,
);

            Kafka::publish()
                ->onTopic(config('kafka.topics.events_raw', 'lms.events.raw'))
                ->withMessage($message)
                ->send();

            return true;
        } catch (\Throwable $e) {
            Log::error('Kafka publish failed', [
                'event_id' => $event->event_id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * GET /api/events/recent
     * Lista los últimos eventos persistidos. Útil para validación manual.
     */
    public function recent(): JsonResponse
    {
        return response()->json([
            'total' => RawEvent::count(),
            'events' => RawEvent::orderByDesc('created_at')->limit(20)->get(),
        ]);
    }
}
