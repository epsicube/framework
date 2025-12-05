<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Facades;

use EpsicubeModules\MailingSystem\Contracts\Mailer;
use EpsicubeModules\MailingSystem\Registries\MailersRegistry;
use Illuminate\Support\Facades\Facade;

class Mailers extends Facade
{
    public static string $accessor = MailersRegistry::class;

    protected static function getFacadeAccessor(): string
    {
        return static::$accessor;
    }

    public static function register(Mailer ...$items): void
    {
        static::resolved(function (MailersRegistry $registry) use ($items) {
            $registry->register(...$items);
        });
    }
}
