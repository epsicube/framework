<?php

declare(strict_types=1);

namespace Epsicube\Support\Exceptions;

use Epsicube\Schemas\Schema;
use RuntimeException;

class UnresolvableOptionException extends RuntimeException
{
    public static function forSchema(Schema $schema, string $name): self
    {
        $message = sprintf("No value provided for property '%s' in group '%s' and no default is defined.", $name, $schema->identifier());

        return new static($message);
    }
}
