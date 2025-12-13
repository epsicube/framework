<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Resolvers;

use Closure;
use Epsicube\Schemas\Contracts\EnumResolver;
use Epsicube\Schemas\Types\EnumCase;
use LogicException;

class DynamicEnumResolver implements EnumResolver
{
    /** @var Closure():array<EnumCase> */
    protected Closure $callback;

    /** @var EnumCase[]|null */
    protected ?array $cases = null;

    /**
     * @param  Closure():array<EnumCase>  $callback
     */
    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @return EnumCase[]
     */
    public function cases(): array
    {
        if ($this->cases === null) {
            $cases = ($this->callback)();

            if (! is_array($cases)) {
                throw new LogicException('Dynamic enum resolver must return an array of EnumCase.');
            }

            $this->cases = $cases;
        }

        return $this->cases;
    }
}
