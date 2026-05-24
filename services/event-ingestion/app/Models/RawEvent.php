<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RawEvent extends Model
{
    use HasUuids;

    protected $primaryKey = 'event_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'event_id',
        'occurred_at',
        'lms_source',
        'institution_id',
        'student_external_id',
        'course_external_id',
        'event_type',
        'raw_payload',
        'canonical_payload',
        'published_to_kafka',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'raw_payload' => 'array',
        'canonical_payload' => 'array',
        'published_to_kafka' => 'boolean',
    ];

    public function uniqueIds(): array
    {
        return ['event_id'];
    }
}
