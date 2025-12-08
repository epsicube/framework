<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Contracts;

interface Property
{
    public function getTitle(): ?string;

    public function getDescription(): ?string;

    public function isRequired(): bool;

    public function isNullable(): bool;

    public function getDefault(): mixed;
}
