<?php

namespace App\Services\Ingestion\Adapters;

/**
 * Adaptador Open edX.
 * Formato edX:
 *  {
 *    "event_type": "edx.course.enrollment.activated",
 *    "event_source": "server",
 *    "context": {"user_id": 99, "course_id": "course-v1:..."},
 *    "time": "2026-05-23T..."
 *  }
 */
class OpenedxAdapter implements LmsAdapterInterface
{
    public function source(): string
    {
        return 'openedx';
    }

    public function toCanonical(array $rawPayload, ?string $institutionId): array
    {
        return [
            'lms_source' => $this->source(),
            'institution_id' => $institutionId,
            'student_external_id' => (string) ($rawPayload['context']['user_id'] ?? 'unknown'),
            'course_external_id' => (string) ($rawPayload['context']['course_id'] ?? 'unknown'),
            'event_type' => $this->mapEventName($rawPayload['event_type'] ?? ''),
            'occurred_at' => $rawPayload['time'] ?? now()->toIso8601String(),
        ];
    }

    private function mapEventName(string $eventType): string
    {
        return match (true) {
            str_contains($eventType, 'enrollment.activated') => 'enrollment',
            str_contains($eventType, 'problem_check') => 'quiz_attempted',
            str_contains($eventType, 'video.played') => 'video_watched',
            str_contains($eventType, 'page_close'), str_contains($eventType, 'page_view') => 'course_viewed',
            str_contains($eventType, 'forum.thread.created') => 'forum_post',
            default => 'unknown_event',
        };
    }
}