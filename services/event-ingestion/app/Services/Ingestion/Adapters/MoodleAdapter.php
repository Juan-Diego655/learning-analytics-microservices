<?php

namespace App\Services\Ingestion\Adapters;

/**
 * Adaptador Moodle.
 * Eventos típicos de Moodle vienen con formato:
 *  {
 *    "eventname": "\\core\\event\\course_viewed",
 *    "userid": 42,
 *    "courseid": 7,
 *    "timecreated": 1709...
 *  }
 */
class MoodleAdapter implements LmsAdapterInterface
{
    public function source(): string
    {
        return 'moodle';
    }

    public function toCanonical(array $rawPayload, ?string $institutionId): array
    {
        return [
            'lms_source' => $this->source(),
            'institution_id' => $institutionId,
            'student_external_id' => (string) ($rawPayload['userid'] ?? 'unknown'),
            'course_external_id' => (string) ($rawPayload['courseid'] ?? 'unknown'),
            'event_type' => $this->mapEventName($rawPayload['eventname'] ?? ''),
            'occurred_at' => isset($rawPayload['timecreated'])
                ? date('c', (int) $rawPayload['timecreated'])
                : now()->toIso8601String(),
        ];
    }

    /**
     * Mapea nombres de eventos de Moodle al vocabulario canónico interno.
     */
    private function mapEventName(string $eventName): string
    {
        return match (true) {
            str_contains($eventName, 'course_viewed') => 'course_viewed',
            str_contains($eventName, 'assignment_submitted') => 'assignment_submitted',
            str_contains($eventName, 'quiz_attempt') => 'quiz_attempted',
            str_contains($eventName, 'forum_discussion') => 'forum_post',
            str_contains($eventName, 'user_loggedin') => 'session_started',
            default => 'unknown_event',
        };
    }
}