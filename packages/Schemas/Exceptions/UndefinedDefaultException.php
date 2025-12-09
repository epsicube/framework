<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Exceptions;

use Epsicube\Schemas\Contracts\Property;
use RuntimeException;

/**
 * Exception thrown when a Property is accessed but has no default value defined.
 *
 * Help: always check using hasDefault() before calling getDefault(),
 * or define a default value for the property.
 */
class UndefinedDefaultException extends RuntimeException
{
    public static function forProperty(Property $property): static
    {
        $message = 'Property does not have a default value defined.';

        if ($title = $property->getTitle()) {
            $message = sprintf("Property '%s' does not have a default value defined.", $title);
        }

        return new static($message);
    }
}
