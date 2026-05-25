<?php

namespace App\Console\Commands;

use App\Services\Notification\NotificationDispatcher;
use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Comando: notifications:consume
 *
 * Proceso largo (long-running) que consume mensajes del exchange 'alerts'
 * de RabbitMQ y los delega al NotificationDispatcher.
 *
 * Ejecucion: php artisan notifications:consume
 * Tipicamente lanzado por supervisord en el contenedor la-notification-worker.
 *
 * Mensajes vienen del Alerting Service via routing_key 'incident.created'.
 *
 * Garantias:
 * - manual ACK: el mensaje solo se elimina de la cola si el dispatch fue OK
 * - retry on disconnect: si RabbitMQ se cae, el comando reintenta cada 5s
 * - prefetch=1: solo procesa 1 mensaje a la vez (fair dispatch)
 */
class ConsumeAlertNotifications extends Command
{
    protected $signature = 'notifications:consume';
    protected $description = 'Consume incidentes desde RabbitMQ exchange alerts y los despacha por canales';

    private const EXCHANGE_NAME = 'alerts';
    private const QUEUE_NAME = 'notification.dispatcher';
    private const ROUTING_KEY = 'incident.created';

    public function handle(): int
    {
        $host = env('RABBITMQ_HOST', 'rabbitmq');
        $port = (int) env('RABBITMQ_PORT', 5672);
        $user = env('RABBITMQ_USER', 'noctua');
        $pass = env('RABBITMQ_PASSWORD', 'noctua_pass');

        $this->info("Conectando a RabbitMQ (Notification Dispatcher)...");
        $this->line("  Host           : {$host}:{$port}");
        $this->line("  Exchange       : " . self::EXCHANGE_NAME);
        $this->line("  Queue          : " . self::QUEUE_NAME);
        $this->line("  Routing key    : " . self::ROUTING_KEY);
        $this->newLine();

        while (true) {
            try {
                $this->runConsumer($host, $port, $user, $pass);
            } catch (\Throwable $e) {
                $this->error("[" . now()->format('H:i:s') . "] Conexion perdida: " . $e->getMessage());
                $this->warn("Reintentando en 5 segundos...");
                sleep(5);
            }
        }
    }

    private function runConsumer(string $host, int $port, string $user, string $pass): void
    {
        $connection = new AMQPStreamConnection($host, $port, $user, $pass);
        $channel = $connection->channel();

        // Declarar el exchange (idempotente, mismo tipo/durable que Alerting)
        $channel->exchange_declare(
            self::EXCHANGE_NAME,
            AMQPExchangeType::TOPIC,
            false,  // passive
            true,   // durable
            false   // auto_delete
        );

        // Declarar la cola del dispatcher (durable, no exclusiva)
        $channel->queue_declare(
            self::QUEUE_NAME,
            false,  // passive
            true,   // durable
            false,  // exclusive
            false   // auto_delete
        );

        // Bind: la cola escucha el exchange con routing_key 'incident.created'
        $channel->queue_bind(self::QUEUE_NAME, self::EXCHANGE_NAME, self::ROUTING_KEY);

        // Fair dispatch: 1 mensaje a la vez por consumer (evita saturacion)
        $channel->basic_qos(null, 1, false);

        $this->info("Esperando mensajes del exchange '" . self::EXCHANGE_NAME . "'...");
        $this->newLine();

        $callback = function (AMQPMessage $msg) {
            $this->processMessage($msg);
        };

        $channel->basic_consume(
            self::QUEUE_NAME,
            '',     // consumer_tag (auto-generado)
            false,  // no_local
            false,  // no_ack (queremos ACK manual)
            false,  // exclusive
            false,  // nowait
            $callback
        );

        // Bloqueo principal: itera mientras haya conexion
        while ($channel->is_open()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }

    private function processMessage(AMQPMessage $msg): void
    {
        $payload = json_decode($msg->getBody(), true);

        if (!is_array($payload)) {
            $this->error("[" . now()->format('H:i:s') . "] Mensaje invalido, descartando");
            $msg->nack(false); // discard, no re-queue
            return;
        }

        $incidentId = $payload['incident_id'] ?? 'unknown';
        $ruleCode = $payload['rule_code'] ?? 'unknown';

        try {
            $dispatcher = new NotificationDispatcher();
            $result = $dispatcher->dispatch($payload);

            $this->line(sprintf(
                "[%s] ✉  %s · %s · sent=%d failed=%d total=%d",
                now()->format('H:i:s'),
                substr($incidentId, 0, 8),
                $ruleCode,
                $result['sent'],
                $result['failed'],
                $result['total']
            ));

            // ACK: mensaje procesado, eliminar de la cola
            $msg->ack();

        } catch (\Throwable $e) {
            $this->error(sprintf(
                "[%s] Error procesando %s: %s",
                now()->format('H:i:s'),
                substr($incidentId, 0, 8),
                $e->getMessage()
            ));

            // NACK con re-queue: que otro consumer lo intente
            $msg->nack(true);
        }
    }
}
