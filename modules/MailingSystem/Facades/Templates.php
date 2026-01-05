<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Facades;

use EpsicubeModules\MailingSystem\Contracts\MailTemplate;
use EpsicubeModules\MailingSystem\Registries\TemplatesRegistry;
use Illuminate\Support\Facades\Facade;

class Templates extends Facade
{
    public static string $accessor = TemplatesRegistry::class;

    protected static function getFacadeAccessor(): string
    {
        return static::$accessor;
    }

    public static function register(MailTemplate ...$items): void
    {
        static::resolved(function (TemplatesRegistry $registry) use ($items): void {
            $registry->register(...$items);
        });
    }
}
