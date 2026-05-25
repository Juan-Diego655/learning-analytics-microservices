<?php

namespace App\Services\Notification\Channels;

/**
 * Slack mock: simula un POST a webhook de Slack.
 *
 * En produccion seria un Guzzle POST a https://hooks.slack.com/services/...
 * Aqui registra el "envio" como exitoso.
 */
class SlackMockChannel implements NotificationChannelInterface
{
    private string $webhookUrl;

    public function __construct(string $webhookUrl = 'https://hooks.slack.com/services/MOCK/WEBHOOK')
    {
        $this->webhookUrl = $webhookUrl;
    }

    public function name(): string
    {
        return 'slack';
    }

    public function recipient(): string
    {
        return $this->webhookUrl;
    }

    public function send(array $incident): bool
    {
        $severity = strtoupper($incident['severity'] ?? 'info');
        $emoji = match($incident['severity'] ?? '') {
            'critical' => '🔴',
            'warning' => '🟡',
            default => '🔵',
        };

        $message = sprintf(
            "%s *%s* | %s\nEstudiante: `%s` | Curso: `%s`",
            $emoji,
            $severity,
            $incident['rule_code'] ?? 'UNKNOWN',
            substr($incident['student_external_id'] ?? '—', 0, 8),
            substr($incident['course_external_id'] ?? '—', 0, 20)
        );

        fwrite(STDERR, sprintf(
            "💬 [slack mock] Channel: #alerts | Message: %s\n",
            str_replace("\n", " · ", $message)
        ));

        return true;
    }
}
