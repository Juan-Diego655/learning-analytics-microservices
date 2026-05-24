<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertRule extends Model
{
    protected $table = 'alert_rules';
    protected $primaryKey = 'rule_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'rule_code', 'name', 'description', 'severity',
        'evaluation_mode', 'config', 'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_CRITICAL = 'critical';

    public const MODE_STREAM = 'stream';
    public const MODE_BATCH = 'batch';
}
