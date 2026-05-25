<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSent extends Model
{
    protected $table = 'notifications_sent';

    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_SLACK = 'slack';
    public const CHANNEL_LOG = 'log';

    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'incident_id',
        'rule_code',
        'severity',
        'channel',
        'recipient',
        'status',
        'payload',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
    ];

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeForIncident($query, string $incidentId)
    {
        return $query->where('incident_id', $incidentId);
    }
}
