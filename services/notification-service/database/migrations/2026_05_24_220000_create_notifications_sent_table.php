<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla notifications_sent
 *
 * Registra cada intento de envio de notificacion procesado por el
 * Notification Dispatcher al consumir mensajes del exchange 'alerts' de
 * RabbitMQ. Cada incidente puede generar multiples filas (una por canal).
 *
 * No tiene FK a alert_incidents porque es un microservicio distinto con
 * su propia BD (Database per Service, ADR-03). La columna incident_id
 * es una FK logica.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications_sent', function (Blueprint $table) {
            $table->bigIncrements('id');

            // FK logica al incidente que disparo la notificacion
            $table->uuid('incident_id')->index();

            // Identificacion del incidente (para no tener que cruzar BDs en cada query)
            $table->string('rule_code', 16)->index();
            $table->string('severity', 16);

            // Canal de envio: email, slack, log
            $table->string('channel', 32)->index();

            // Destino del envio (para email: address; slack: webhook url; log: stderr)
            $table->string('recipient', 255);

            // Estado del envio: sent | failed
            $table->string('status', 16)->index();

            // Payload completo del incidente (para auditoria y replay)
            $table->jsonb('payload')->nullable();

            // Mensaje de error si status=failed
            $table->text('error_message')->nullable();

            // Cuando se completo (o fallo) el envio
            $table->timestamp('sent_at')->useCurrent();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_sent');
    }
};
