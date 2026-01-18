<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Resolvers;

use Closure;
use Epsicube\Schemas\Contracts\EnumResolver;
use Epsicube\Schemas\Types\EnumCase;
use Laravel\SerializableClosure\SerializableClosure;
use LogicException;
use ReflectionClass;

class DynamicEnumResolver implements EnumResolver
{
    protected SerializableClosure $callback;

    /** @var EnumCase[]|null */
    protected ?array $cases = null;

    /**
     * @param  Closure():array<EnumCase>  $callback
     */
    public function __construct(Closure $callback)
    {
        $this->callback = new SerializableClosure($callback);
    }

    public static function __set_state(array $properties): static
    {
        $instance = (new ReflectionClass(static::class))->newInstanceWithoutConstructor();

        foreach ($properties as $key => $value) {
            $instance->{$key} = $value;
        }

        return $instance;
    }

    /**
     * @return EnumCase[]
     */
    public function cases(): array
    {
        if ($this->cases === null) {
            $cases = ($this->callback->getClosure())();

            if (! is_array($cases)) {
                throw new LogicException('Dynamic enum resolver must return an array of EnumCase.');
            }

            $this->cases = $cases;
        }

        return $this->cases;
    }
}
