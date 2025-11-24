<?php

declare(strict_types=1);

namespace UniGaleModules\MailingSystem\Facades;

use Illuminate\Support\Facades\Facade;

class Mailers extends Facade
{
    public static string $accessor = 'unigale-mail::mailers';

    protected static function getFacadeAccessor(): string
    {
        return static::$accessor;
    }
}
