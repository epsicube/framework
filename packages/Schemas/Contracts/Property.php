<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Contracts;

use Epsicube\Schemas\Exceptions\UndefinedDefaultException;

interface Property
{
    public function getTitle(): ?string;

    public function getDescription(): ?string;

    public function isRequired(): bool;

    public function isNullable(): bool;

    public function hasDefault(): bool;

    /**
     * @throws UndefinedDefaultException
     */
    public function getDefault(): mixed;
}
