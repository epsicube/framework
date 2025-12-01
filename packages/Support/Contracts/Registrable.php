<?php

declare(strict_types=1);

namespace UniGale\Support\Contracts;

interface Registrable
{
    /**
     * @return string this identifier needs to be unique across all registry items
     */
    public function identifier(): string;
}
