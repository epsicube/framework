<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Models;

use EpsicubeModules\ExecutionPlatform\Enum\WorkflowEventType;
use Illuminate\Database\Eloquent\Model;

class WorkflowEvent extends Model
{
    protected $table = 'workflow_events';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'type'       => WorkflowEventType::class,
        'created_at' => 'datetime',
    ];

    public function workflow()
    {
        return $this->belongsTo(WorkflowModel::class, 'workflow_id');
    }
}
