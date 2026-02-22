<?php

declare(strict_types=1);

namespace Epsicube\Support\Modules;

use Epsicube\Support\Concerns\Condition as ConditionConcern;
use Epsicube\Support\Enums\ConditionState;
use Epsicube\Support\Modules\Conditions\AllCondition;
use Epsicube\Support\Modules\Conditions\AnyCondition;
use Epsicube\Support\Modules\Conditions\DatabaseDrivers;
use Epsicube\Support\Modules\Conditions\EpsicubeVersion;
use Epsicube\Support\Modules\Conditions\PhpExtensions;
use Epsicube\Support\Modules\Conditions\PhpVersion;
use Epsicube\Support\Modules\Conditions\PostgresqlExtensions;
use RuntimeException;

readonly class Condition
{
    public static function phpVersion(string $version): PhpVersion
    {
        return new PhpVersion($version);
    }

    public static function epsicubeVersion(string $version): EpsicubeVersion
    {
        return new EpsicubeVersion($version);
    }

    public static function phpExtensions(string ...$extensions): PhpExtensions
    {
        return new PhpExtensions(...$extensions);
    }

    public static function databaseDrivers(string|array $drivers, ?string $connection = null): DatabaseDrivers
    {
        return new DatabaseDrivers($drivers, $connection);
    }

    public static function postgresqlExtensions(string|array $extensions, ?string $connection = null): PostgresqlExtensions
    {
        return new PostgresqlExtensions($extensions, $connection);
    }

    public static function any(ConditionConcern ...$conditions): AnyCondition
    {
        return new AnyCondition(...$conditions);
    }

    public static function all(ConditionConcern ...$conditions): AllCondition
    {
        return new AllCondition(...$conditions);
    }

    public static function when(ConditionConcern $condition, array $conditions, ConditionState $state = ConditionState::VALID): array
    {
        return array_map(function ($target) use ($condition, $state) {
            if (! $target instanceof ConditionConcern) {
                throw new RuntimeException(sprintf(
                    'All elements passed to Condition::when must extend %s. Got %s.',
                    ConditionConcern::class,
                    get_debug_type($target)
                ));
            }

            return $target->skipWhen(function () use ($condition, $state) {
                return $condition->run() !== $state;
            });
        }, $conditions);
    }
}
