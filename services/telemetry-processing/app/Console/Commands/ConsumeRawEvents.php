<?php

namespace App\Console\Commands;

use App\Services\Telemetry\WindowAggregator;
use Illuminate\Console\Command;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Facades\Kafka;

/**
 * ConsumeRawEvents
 * -----------------------------------------------------------------------------
 * Worker que consume el topic Kafka lms.events.raw publicado por Event Ingestion,
 * y delega el procesamiento al WindowAggregator.
 *
 * Se ejecuta como un proceso largo: `php artisan kafka:consume-events`.
 * En el contenedor `la-telemetry-worker` corre indefinidamente.
 */
class ConsumeRawEvents extends Command
{
    protected $signature = 'kafka:consume-events';
    protected $description = 'Consume el topic lms.events.raw y agrega métricas en ventanas de 5 min';

    public function handle(WindowAggregator $aggregator): int
    {
        $topic = config('kafka.topics.events_raw', 'lms.events.raw');
        $consumerGroup = env('KAFKA_CONSUMER_GROUP', 'telemetry-processor');

        $this->info("Conectando a Kafka...");
        $this->info("  Topic         : $topic");
        $this->info("  Consumer group: $consumerGroup");
        $this->info("  Brokers       : " . config('kafka.brokers'));
        $this->newLine();
        $this->info("Esperando mensajes (Ctrl+C para detener)...");
        $this->newLine();

        $consumer = Kafka::consumer([$topic], $consumerGroup)
    ->withAutoCommit()
    ->withOptions([
        'auto.offset.reset' => 'earliest',
    ])
    ->withHandler(function (ConsumerMessage $message) use ($aggregator) {
                try {
                    $body = $message->getBody();

                    // El body puede venir como array (decodificado) o string (json)
                    $event = is_array($body) ? $body : json_decode($body, true);

                    if (! is_array($event)) {
                        $this->warn("Mensaje ignorado (no es JSON válido)");
                        return;
                    }

                    $result = $aggregator->process($event);

                    $status = $result['status'];
                    $eventId = substr($result['event_id'] ?? '?', 0, 8);

                    if ($status === 'processed') {
                        $this->line(sprintf(
                            "[%s] %s · count=%d · students=%d",
                            now()->format('H:i:s'),
                            $eventId,
                            $result['event_count'],
                            $result['unique_students']
                        ));
                    } else {
                        $this->comment("[" . now()->format('H:i:s') . "] $eventId · $status");
                    }
                } catch (\Throwable $e) {
                    $this->error("Error procesando mensaje: " . $e->getMessage());
                    \Log::error('Telemetry consumer error', [
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
