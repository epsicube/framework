<?php

declare(strict_types=1);

namespace UniGaleModules\MailingSystem\Facades;

use Illuminate\Support\Facades\Facade;
use UniGaleModules\MailingSystem\Contracts\MailTemplate;
use UniGaleModules\MailingSystem\Registries\TemplatesRegistry;

class Templates extends Facade
{
    public static string $accessor = TemplatesRegistry::class;

    protected static function getFacadeAccessor(): string
    {
        return static::$accessor;
    }

    public static function register(MailTemplate ...$items): void
    {
        static::resolved(function (TemplatesRegistry $registry) use ($items) {
            $registry->register(...$items);
        });
    }
}
