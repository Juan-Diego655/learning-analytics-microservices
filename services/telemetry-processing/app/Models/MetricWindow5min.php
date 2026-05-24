<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetricWindow5min extends Model
{
    protected $table = 'metrics_window_5min';

    protected $fillable = [
        'window_start',
        'window_end',
        'institution_id',
        'course_external_id',
        'event_type',
        'event_count',
        'unique_students',
        'student_ids',
    ];

    protected $casts = [
        'window_start' => 'datetime',
        'window_end' => 'datetime',
        'student_ids' => 'array',
    ];
}
