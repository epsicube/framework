<?php

declare(strict_types=1);

namespace Epsicube\Support\Modules;

class Identity
{
    public string $name;

    public string $author = 'Anonymous';

    public ?string $description = null;

    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function author(string $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function description(?string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
