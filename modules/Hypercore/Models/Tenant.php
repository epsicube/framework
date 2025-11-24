<?php

declare(strict_types=1);

namespace UniGaleModules\Hypercore\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Encryption\Encrypter;
use UniGaleModules\Hypercore\Facades\Hypercore;

/**
 * @property-read  int $id
 * @property-read  int $key
 * @property string $identifier Tenant identifier (unique)
 * @property string $name Tenant display name
 *
 * URL
 * @property string|null $scheme http|https (null=any)
 * @property string $domain
 * @property string|null $path
 *
 * Localization
 * @property string $locale IETF language tag (e.g. en_US, fr_FR)
 * @property string $timezone
 *
 * Extras
 * @property bool $debug
 * @property bool $maintenance
 * @property array|null $_maintenance_data
 * @property array|null $config_overrides
 *
 * Accessors
 * @property string $url
 */
class Tenant extends Model
{
    protected $table = 'hypercore_tenants';

    public $timestamps = false;

    protected static $unguarded = true; // <- empty $guarded prevent _{field} assignation

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (! $model->key) {
                $model->key = 'base64:'.base64_encode(Encrypter::generateKey(config('app.cipher')));
            }
        });

        static::updated(function () {
            Hypercore::updateCache();
        });
    }

    protected function casts(): array
    {
        return [
            'debug'             => 'boolean',
            'maintenance'       => 'boolean',
            'config_overrides'  => 'array',
            '_maintenance_data' => 'array',
        ];
    }

    public function url(): Attribute
    {
        return Attribute::make(
            get: fn () => mb_rtrim(implode('', array_filter([
                $this->scheme ? "{$this->scheme}://" : null,
                $this->domain,
                $this->path ? '/'.mb_ltrim($this->path, '/') : null,
            ])), '/')
        );
    }
}
