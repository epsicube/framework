<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowModel extends Model
{
    protected $table = 'workflows';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'input'  => 'array',
            'result' => 'array',
        ];
    }

    public function events()
    {
        return $this->hasMany(WorkflowEvent::class, 'workflow_id')->orderBy('id');
    }
}
