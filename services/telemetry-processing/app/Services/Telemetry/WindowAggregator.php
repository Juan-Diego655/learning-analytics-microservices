<?php

namespace App\Services\Telemetry;

use App\Models\EventProcessed;
use App\Models\MetricWindow5min;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * WindowAggregator
 * -----------------------------------------------------------------------------
 * Procesa un evento canónico recibido desde Kafka y lo agrega a la ventana
 * temporal correspondiente. Las ventanas son fijas de 5 minutos (00:00, 00:05,
 * 00:10, ...) y los eventos se agrupan por (institución, curso, tipo_evento).
 *
 * Patrón: Stream Processing + Tumbling Window Aggregation.
 * Idempotente: si el mismo event_id llega dos veces, no se duplica.
 */
class WindowAggregator
{
    private int $windowSeconds;

    public function __construct()
    {
        $this->windowSeconds = (int) config('app.telemetry_window_seconds', 300);
    }

    /**
     * Procesa un evento canónico y actualiza la ventana correspondiente.
     */
    public function process(array $event): array
    {
        // 1. Idempotencia: si ya procesamos este event_id, ignoramos
        $eventId = $event['event_id'] ?? null;
        if (! $eventId) {
            throw new \InvalidArgumentException('event_id es obligatorio');
        }

        if (EventProcessed::where('event_id', $eventId)->exists()) {
            Log::info("Event $eventId ya procesado (idempotencia)");
            return ['status' => 'skipped_duplicate', 'event_id' => $eventId];
        }

        // 2. Calcular window_start (redondeado al múltiplo de 5 min hacia abajo)
        $occurredAt = Carbon::parse($event['occurred_at']);
        $windowStart = $this->roundDownToWindow($occurredAt);
        $windowEnd = $windowStart->copy()->addSeconds($this->windowSeconds);

        // 3. Transacción: registrar evento + actualizar/crear métrica
        return DB::transaction(function () use ($event, $eventId, $windowStart, $windowEnd) {
            // 3.1 Marcar evento como procesado
            EventProcessed::create([
                'event_id' => $eventId,
                'occurred_at' => $event['occurred_at'],
                'lms_source' => $event['lms_source'],
                'institution_id' => $event['institution_id'],
                'student_external_id' => $event['student_external_id'],
                'course_external_id' => $event['course_external_id'],
                'event_type' => $event['event_type'],
            ]);

            // 3.2 Upsert en metrics_window_5min
            $metric = MetricWindow5min::firstOrNew([
                'window_start' => $windowStart,
                'institution_id' => $event['institution_id'],
                'course_external_id' => $event['course_external_id'],
                'event_type' => $event['event_type'],
            ]);

            if (! $metric->exists) {
                $metric->window_end = $windowEnd;
                $metric->event_count = 0;
                $metric->unique_students = 0;
                $metric->student_ids = [];
            }

            // Actualizar contadores
            $studentIds = $metric->student_ids ?? [];
            $studentId = $event['student_external_id'];
            $isNewStudent = ! in_array($studentId, $studentIds, true);

            $metric->event_count = ($metric->event_count ?? 0) + 1;
            if ($isNewStudent) {
                $studentIds[] = $studentId;
                $metric->student_ids = $studentIds;
                $metric->unique_students = count($studentIds);
            }

            $metric->save();

            return [
                'status' => 'processed',
                'event_id' => $eventId,
                'window_start' => $windowStart->toIso8601String(),
                'metric_id' => $metric->id,
                'event_count' => $metric->event_count,
                'unique_students' => $metric->unique_students,
            ];
        });
    }

    /**
     * Redondea un timestamp hacia abajo al múltiplo de la ventana.
     * Ej: 16:07:32 con ventana 5min → 16:05:00
     */
    private function roundDownToWindow(Carbon $time): Carbon
    {
        $epoch = $time->timestamp;
        $rounded = $epoch - ($epoch % $this->windowSeconds);
        return Carbon::createFromTimestamp($rounded);
    }
}
