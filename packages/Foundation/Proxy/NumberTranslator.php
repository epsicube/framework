<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Proxy;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Number;

class NumberTranslator implements Translator
{
    public function __construct(
        protected Translator $originalTranslator
    ) {}

    public function get($key, array $replace = [], $locale = null, $fallback = true): mixed
    {
        $line = $this->originalTranslator->get($key, $replace, $locale, $fallback);

        return is_string($line) ? $this->formatIfNecessary($line, $replace, $locale) : $line;
    }

    public function choice($key, $number, array $replace = [], $locale = null): string
    {
        $replace['count'] ??= $number;

        $line = $this->originalTranslator->choice($key, $number, $replace, $locale);

        return $this->formatIfNecessary($line, $replace, $locale);
    }

    /**
     * Decorates the translator to format numbers using the `:%key:precision:maxPrecision` syntax.
     *
     * @example ":%amount" -> "1,234.57" (Standard localized format)
     * @example ":%amount:0" -> "1,235" (Fixed precision, no decimals)
     * @example ":%amount:3" -> "1,234.568" (Fixed precision, 3 decimals)
     * @example ":%amount::2" -> "1,234.57" (Max precision only: up to 2 decimals)
     * @example ":%amount:2:4" -> "1,234.5678" (Range: between 2 and 4 decimals)
     * @example ":%amount:0\:20" -> "1,234:20" (Escapes the colon)
     */
    protected function formatIfNecessary(string $line, array $replace, ?string $locale): string
    {
        if (! str_contains($line, ':%')) {
            return $line;
        }

        $line = preg_replace_callback('/:%(\w+)(?::(?=[\d:]))?(\d*)(?::(?=\d))?(\d*)/', function ($m) use ($replace, $locale) {
            $val = $replace[$m[1]] ?? null;

            if (! is_numeric($val)) {
                return (string) ($val ?? $m[0]);
            }

            return Number::format($val,
                precision: ($m[2] !== '') ? (int) $m[2] : null,
                maxPrecision: ($m[3] !== '') ? (int) $m[3] : null,
                locale: $locale ?? $this->getLocale()
            );
        }, $line);

        return str_replace('\:', ':', $line);
    }

    // --- Proxy Methods ---
    public function getLocale(): string
    {
        return $this->originalTranslator->getLocale();
    }

    public function setLocale($locale): void
    {
        $this->originalTranslator->setLocale($locale);
    }

    public function __call($method, $args)
    {
        return $this->originalTranslator->{$method}(...$args);
    }
}
