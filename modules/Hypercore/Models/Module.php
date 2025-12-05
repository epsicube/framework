<?php

declare(strict_types=1);

namespace EpsicubeModules\Hypercore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property string $identifier
 * @property bool $enabled
 *
 * RELATIONS
 * @property int $tenant_id
 * @property-read Tenant $tenant
 */
class Module extends Model
{
    protected $table = 'hypercore_modules';

    public $timestamps = false;

    protected static $unguarded = true; // <- empty $guarded prevent _{field} assignation

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
