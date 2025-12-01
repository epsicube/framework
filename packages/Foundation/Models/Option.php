<?php

declare(strict_types=1);

namespace UniGale\Foundation\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property string $group
 * @property string $key
 * @property mixed $value
 */
class Option extends Model
{
    protected $table = 'options';

    public $timestamps = false;

    protected static $unguarded = true; // <- empty $guarded prevent _{field} assignation

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }
}
