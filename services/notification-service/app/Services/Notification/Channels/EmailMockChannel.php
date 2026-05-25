<?php

namespace App\Services\Notification\Channels;

/**
 * Email mock: simula el envio de un correo SMTP.
 *
 * En produccion seria reemplazado por Resend, SendGrid o SES.
 * Aqui simplemente registra el "envio" como exitoso y loguea.
 */
class EmailMockChannel implements NotificationChannelInterface
{
    private string $recipient;

    public function __construct(string $recipient = 'admin@learning.test')
    {
        $this->recipient = $recipient;
    }

    public function name(): string
    {
        return 'email';
    }

    public function recipient(): string
    {
        return $this->recipient;
    }

    public function send(array $incident): bool
    {
        // Simulacion: 5% de fallo aleatorio para demostrar manejo de errores
        if (mt_rand(1, 100) <= 5) {
            throw new \Exception('SMTP timeout (simulado)');
        }

        $subject = sprintf(
            '[%s] %s - %s',
            strtoupper($incident['severity'] ?? 'info'),
            $incident['rule_code'] ?? 'UNKNOWN',
            $incident['rule_name'] ?? 'Incidente'
        );

        fwrite(STDERR, sprintf(
            "📧 [email mock] To: %s | Subject: %s\n",
            $this->recipient,
            $subject
        ));

        return true;
    }
}
