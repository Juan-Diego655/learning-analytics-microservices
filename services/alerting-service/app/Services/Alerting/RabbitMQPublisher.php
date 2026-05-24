<?php

namespace App\Services\Alerting;

use App\Models\AlertIncident;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * RabbitMQPublisher
 * -----------------------------------------------------------------------------
 * Publica incidentes al exchange 'alerts' de RabbitMQ usando topic routing.
 * Notification Dispatcher (D6) consumira de este exchange.
 *
 * Topology:
 *   exchange: 'alerts' (type: topic, durable)
 *   routing key: 'incident.created' (otros: incident.resolved, etc.)
 *
 * Decision arquitectonica:
 *   - Usamos RabbitMQ (no Kafka) para esta etapa porque:
 *     a) Los eventos derivados (incidentes) tienen volumetria mucho menor
 *     b) Notification Dispatcher requiere acknowledgement por mensaje
 *     c) RabbitMQ ofrece routing por topic mas flexible que partitioning
 *   - Fase 1 documenta este patron hibrido: Kafka para eventos crudos
 *     de alto volumen, RabbitMQ para comandos y eventos derivados.
 */
class RabbitMQPublisher
{
    private string $host;
    private int $port;
    private string $user;
    private string $password;
    private string $vhost;
    private string $exchange;

    public function __construct()
    {
        $this->host = env('RABBITMQ_HOST', 'rabbitmq');
        $this->port = (int) env('RABBITMQ_PORT', 5672);
        $this->user = env('RABBITMQ_USER', 'noctua');
        $this->password = env('RABBITMQ_PASSWORD', 'noctua_pass');
        $this->vhost = env('RABBITMQ_VHOST', '/');
        $this->exchange = env('RABBITMQ_EXCHANGE', 'alerts');
    }

    /**
     * Publica un incidente al exchange alerts con routing key incident.created.
     * Devuelve true si la publicacion fue exitosa.
     */
    public function publishIncidentCreated(AlertIncident $incident): bool
    {
        try {
            $connection = new AMQPStreamConnection(
                $this->host, $this->port, $this->user, $this->password, $this->vhost
            );
            $channel = $connection->channel();

            // Declarar exchange durable (idempotente, si ya existe no hace nada)
            $channel->exchange_declare(
                exchange: $this->exchange,
                type: 'topic',
                passive: false,
                durable: true,
                auto_delete: false
            );

            $routingKey = env('RABBITMQ_ROUTING_KEY_INCIDENT', 'incident.created');

            $body = json_encode([
                'incident_id' => $incident->incident_id,
                'rule_code' => $incident->rule_code,
                'severity' => $incident->severity,
                'institution_id' => $incident->institution_id,
                'student_external_id' => $incident->student_external_id,
                'course_external_id' => $incident->course_external_id,
                'trigger_context' => $incident->trigger_context,
                'triggered_at' => $incident->triggered_at?->toIso8601String(),
            ], JSON_UNESCAPED_UNICODE);

            $message = new AMQPMessage($body, [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]);

            $channel->basic_publish($message, $this->exchange, $routingKey);

            $channel->close();
            $connection->close();

            Log::info("Incident {$incident->incident_id} published to RabbitMQ", [
                'exchange' => $this->exchange,
                'routing_key' => $routingKey,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to publish incident to RabbitMQ", [
                'incident_id' => $incident->incident_id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
