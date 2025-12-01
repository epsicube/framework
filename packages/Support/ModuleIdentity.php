<?php

declare(strict_types=1);

namespace UniGale\Support;

class ModuleIdentity
{
    public function __construct(
        public string $name,
        public string $version,
        public string $author,
        public ?string $description = null,
    ) {}

    public static function make(
        string $name,
        string $version,
        string $author,
        ?string $description = null,
    ): static {
        return new static(name: $name, version: $version, author: $author, description: $description);
    }
}
