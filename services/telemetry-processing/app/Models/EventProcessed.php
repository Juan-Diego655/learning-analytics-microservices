<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventProcessed extends Model
{
    protected $table = 'events_processed';
    protected $primaryKey = 'event_id';
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';

    protected $fillable = [
        'event_id',
        'occurred_at',
        'lms_source',
        'institution_id',
        'student_external_id',
        'course_external_id',
        'event_type',
        'processed_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
