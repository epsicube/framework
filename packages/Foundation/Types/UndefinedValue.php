<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Types;

use JsonSerializable;
use LogicException;

/**
 * Represents the absence of value.
 *
 * This sentinel type is used to distinguish between:
 *   - a property explicitly set with a value (including `null`)
 *   - a property with no default value declared by the user
 *
 * In schema definitions, this allows:
 *   - precise handling of `required` versus `nullable`
 *   - correct propagation of defaults during export or validation
 *   - unambiguous differentiation between “default is null” and “no default at all”
 *
 * Instances of this class are never meant to carry data: the type itself
 * expresses semantic intent.
 */
class UndefinedValue implements JsonSerializable
{
    public function jsonSerialize(): null
    {
        return null;
    }

    public function __clone(): void
    {
        throw new LogicException(static::class.' cannot be cloned.');
    }

    public function __sleep(): array
    {
        throw new LogicException(static::class.' cannot be serialized.');
    }

    public function __wakeup(): void
    {
        throw new LogicException(static::class.' cannot be unserialized.');
    }
}
