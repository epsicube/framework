<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Throwable;

trait FormatsMailDates
{
    protected function formatMailDate(mixed $date): ?string
    {
        if (blank($date)) {
            return null;
        }

        try {
            $date = $date instanceof DateTimeInterface
                ? CarbonImmutable::instance($date)
                : CarbonImmutable::parse((string) $date);

            return $date
                ->timezone(config('app.timezone'))
                ->locale(app()->getLocale())
                ->isoFormat('LLL');
        } catch (Throwable) {
            return is_scalar($date) ? (string) $date : null;
        }
    }
}
