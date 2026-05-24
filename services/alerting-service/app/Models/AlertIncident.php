<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertIncident extends Model
{
    use HasUuids;

    protected $table = 'alert_incidents';
    protected $primaryKey = 'incident_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'incident_id', 'rule_code', 'institution_id',
        'student_external_id', 'course_external_id',
        'severity', 'status', 'trigger_context',
        'triggered_at', 'notified_at', 'acknowledged_at', 'resolved_at',
    ];

    protected $casts = [
        'trigger_context' => 'array',
        'triggered_at' => 'datetime',
        'notified_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function uniqueIds(): array { return ['incident_id']; }

    public const STATUS_TRIGGERED = 'triggered';
    public const STATUS_NOTIFIED = 'notified';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_RESOLVED = 'resolved';

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class, 'rule_code', 'rule_code');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AlertIncidentEvent::class, 'incident_id', 'incident_id');
    }
}
