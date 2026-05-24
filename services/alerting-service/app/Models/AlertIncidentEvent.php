<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertIncidentEvent extends Model
{
    protected $table = 'alert_incident_events';
    public $timestamps = false;

    protected $fillable = [
        'incident_id', 'event_type', 'payload', 'occurred_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];
}
