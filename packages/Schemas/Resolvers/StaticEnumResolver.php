<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Resolvers;

use Epsicube\Schemas\Contracts\EnumResolver;
use Epsicube\Schemas\Types\EnumCase;

class StaticEnumResolver implements EnumResolver
{
    /** @var EnumCase[] */
    protected array $cases = [];

    public function __construct(EnumCase ...$cases)
    {
        $this->cases = $cases;
    }

    /**
     * @return EnumCase[]
     */
    public function cases(): array
    {
        return $this->cases;
    }
}
