<?php

declare(strict_types=1);

namespace Epsicube\Support\Exceptions;

use Epsicube\Schemas\Schema;
use RuntimeException;

class MissingRequiredOptionsException extends RuntimeException
{
    /**
     * Create an exception for missing required keys in a schema.
     *
     * @param  Schema  $schema  The schema being validated
     * @param  array<string>  $missingKeys  List of missing required keys
     */
    public static function forSchema(Schema $schema, array $missingKeys): static
    {
        $schemaId = $schema->identifier();
        $keys = implode(', ', $missingKeys);
        $message = sprintf('The following required options are missing for schema [%s]: %s', $schemaId, $keys);

        return new static($message);
    }
}
