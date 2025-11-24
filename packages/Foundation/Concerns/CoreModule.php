<?php

declare(strict_types=1);

namespace UniGale\Foundation\Concerns;

abstract class CoreModule extends Module
{
    public static string $prefix = 'core::';

    abstract protected function coreIdentifier(): string;

    public function identifier(): string
    {
        return static::$prefix.$this->coreIdentifier();
    }

    public function author(): string
    {
        return __('Core Team');
    }
}
