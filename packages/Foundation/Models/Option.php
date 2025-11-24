<?php

declare(strict_types=1);

namespace UniGale\Foundation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use UniGaleModules\Hypercore\Models\Tenant;

/**
 * @property-read int $id
 * @property string $module_identifier
 * @property string $key
 * @property mixed $value
 * @property bool $autoload
 *
 * RELATIONS
 * @property int|null $tenant_id
 * @property-read Tenant $tenant
 */
class Option extends Model
{
    protected $table = 'options';

    public $timestamps = false;

    protected static $unguarded = true; // <- empty $guarded prevent _{field} assignation

    protected function casts(): array
    {
        return [
            'value'    => 'json',
            'autoload' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
