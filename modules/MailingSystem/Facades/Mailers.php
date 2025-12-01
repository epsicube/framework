<?php

declare(strict_types=1);

namespace UniGaleModules\MailingSystem\Facades;

use Illuminate\Support\Facades\Facade;
use UniGaleModules\MailingSystem\Contracts\Mailer;
use UniGaleModules\MailingSystem\Registries\MailersRegistry;

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
