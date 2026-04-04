<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowSignal extends Model
{
    protected $table = 'workflow_signals';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];

    public function workflow()
    {
        return $this->belongsTo(WorkflowModel::class, 'workflow_id');
    }
}
