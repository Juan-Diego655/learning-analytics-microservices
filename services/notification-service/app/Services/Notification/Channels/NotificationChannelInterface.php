<?php

namespace App\Services\Notification\Channels;

/**
 * Strategy Pattern: cada canal implementa esta interfaz.
 *
 * Permite agregar nuevos canales (SMS, Teams, Webhook custom)
 * sin modificar el Dispatcher, cumpliendo Open/Closed Principle.
 */
interface NotificationChannelInterface
{
    /**
     * Identificador unico del canal (email, slack, log).
     */
    public function name(): string;

    /**
     * Destinatario por defecto del canal.
     * Email -> direccion, Slack -> webhook URL, Log -> stderr.
     */
    public function recipient(): string;

    /**
     * Envia la notificacion del incidente.
     *
     * @param array $incident Payload completo del incidente
     * @return bool true si fue exitoso, false si fallo
     * @throws \Exception si hay un error que requiere re-queue
     */
    public function send(array $incident): bool;
}
