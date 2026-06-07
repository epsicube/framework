<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Models;

use EpsicubeModules\ExecutionPlatform\Enums\WorkflowEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutionLog extends Model
{
    protected $table = 'execution_logs';

    protected static $unguarded = true;

    public $timestamps = true;

    protected $dateFormat = 'Y-m-d\TH:i:s.uP'; // <- force microseconds and timezone

    protected function casts(): array
    {
        return [
            'type'       => WorkflowEventType::class,
            'payload'    => 'json',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(Execution::class, 'execution_id');
    }
}
