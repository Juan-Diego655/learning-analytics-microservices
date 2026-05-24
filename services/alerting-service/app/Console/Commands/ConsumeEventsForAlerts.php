<?php

namespace App\Console\Commands;

use App\Services\Alerting\RuleDispatcher;
use Illuminate\Console\Command;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Facades\Kafka;

/**
 * ConsumeEventsForAlerts
 * -----------------------------------------------------------------------------
 * Worker que consume el topic Kafka lms.events.raw (mismo que Telemetry)
 * pero con consumer group propio (alerting-engine), garantizando que
 * Alerting y Telemetry procesan los eventos de forma independiente.
 *
 * Por cada evento, invoca el RuleDispatcher que evalua reglas STREAM (R1, R2).
 * Las reglas BATCH (R3) corren con artisan alerts:evaluate-batch (schedule).
 */
class ConsumeEventsForAlerts extends Command
{
    protected $signature = 'kafka:consume-events-for-alerts';
    protected $description = 'Consume lms.events.raw y evalua reglas STREAM (R1, R2)';

    public function handle(RuleDispatcher $dispatcher): int
    {
        $topic = config('kafka.topics.events_raw', 'lms.events.raw');
        $consumerGroup = env('KAFKA_CONSUMER_GROUP', 'alerting-engine');

        $this->info("Conectando a Kafka (Alerting Engine)...");
        $this->info("  Topic         : $topic");
        $this->info("  Consumer group: $consumerGroup");
        $this->info("  Offset reset  : " . env('KAFKA_AUTO_OFFSET_RESET', 'latest'));
        $this->newLine();
        $this->info("Esperando eventos...");
        $this->newLine();

        $offsetReset = env('KAFKA_AUTO_OFFSET_RESET', 'latest');

        $consumer = Kafka::consumer([$topic], $consumerGroup)
            ->withAutoCommit()
            ->withOptions([
                'auto.offset.reset' => $offsetReset,
            ])
            ->withHandler(function (ConsumerMessage $message) use ($dispatcher) {
                try {
                    $body = $message->getBody();
                    $event = is_array($body) ? $body : json_decode($body, true);

                    if (! is_array($event) || ! isset($event['event_id'])) {
                        $this->warn("Mensaje ignorado (sin event_id)");
                        return;
                    }

                    $triggered = $dispatcher->dispatchStream($event);

                    if (! empty($triggered)) {
                        $eventId = substr($event['event_id'], 0, 8);
                        $studentId = $event['student_external_id'] ?? '?';
                        $this->line(sprintf(
                            "[%s] 🚨 %s · student=%s · rules=[%s]",
                            now()->format('H:i:s'),
                            $eventId,
                            $studentId,
                            implode(',', $triggered)
                        ));
                    }
                } catch (\Throwable $e) {
                    $this->error("Error procesando mensaje: " . $e->getMessage());
                    \Log::error('Alerting consumer error', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            })
            ->build();

        $consumer->consume();

        return self::SUCCESS;
    }
}
