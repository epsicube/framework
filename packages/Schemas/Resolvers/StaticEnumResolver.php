<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Resolvers;

use Epsicube\Schemas\Contracts\EnumResolver;
use Epsicube\Schemas\Types\EnumCase;
use ReflectionClass;
use ReflectionException;

class StaticEnumResolver implements EnumResolver
{
    /** @var EnumCase[] */
    protected array $cases = [];

    public function __construct(EnumCase ...$cases)
    {
        $this->cases = $cases;
    }

    /**
     * Keep to ensure var_export and require work for cache
     *
     * @throws ReflectionException
     */
    public static function __set_state(array $properties): static
    {
        $instance = (new ReflectionClass(static::class))->newInstanceWithoutConstructor();

        foreach ($properties as $key => $value) {
            if (property_exists($instance, $key)) {
                $instance->{$key} = $value;
            }
        }

        return $instance;
    }

    /**
     * @return EnumCase[]
     */
    public function cases(): array
    {
        return $this->cases;
    }
}
