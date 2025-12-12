<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Exceptions;

use Epsicube\Schemas\Contracts\Property;
use Epsicube\Schemas\Schema;
use RuntimeException;

class DuplicatePropertyException extends RuntimeException
{
    public static function forSchema(Schema $schema, string $name): static
    {
        $message = sprintf("Property '%s' already exists in schema '%s'.", $name, $schema->identifier());

        return new static($message);
    }

    public static function forProperty(Property $property, string $name): static
    {
        $message = sprintf("Property '%s' already exists in property '%s'.", $name, $property->getTitle());

        return new static($message);
    }
}
