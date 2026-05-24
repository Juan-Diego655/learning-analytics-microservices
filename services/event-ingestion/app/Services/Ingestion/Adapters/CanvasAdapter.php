<?php

namespace App\Services\Ingestion\Adapters;

/**
 * Adaptador Canvas.
 * Formato Canvas:
 *  {
 *    "event": "logged_in",
 *    "user": {"id": "12345"},
 *    "context": {"course_id": "course-101"},
 *    "timestamp": "2026-05-23T..."
 *  }
 */
class CanvasAdapter implements LmsAdapterInterface
{
    public function source(): string
    {
        return 'canvas';
    }

    public function toCanonical(array $rawPayload, ?string $institutionId): array
    {
        return [
            'lms_source' => $this->source(),
            'institution_id' => $institutionId,
            'student_external_id' => (string) ($rawPayload['user']['id'] ?? 'unknown'),
            'course_external_id' => (string) ($rawPayload['context']['course_id'] ?? 'unknown'),
            'event_type' => $this->mapEventName($rawPayload['event'] ?? ''),
            'occurred_at' => $rawPayload['timestamp'] ?? now()->toIso8601String(),
        ];
    }

    private function mapEventName(string $event): string
    {
        return match ($event) {
            'logged_in' => 'session_started',
            'assignment_submitted' => 'assignment_submitted',
            'quiz_submitted' => 'quiz_attempted',
            'page_viewed', 'course_viewed' => 'course_viewed',
            'discussion_topic_created' => 'forum_post',
            default => 'unknown_event',
        };
    }
}