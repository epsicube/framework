<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Enums;

enum StringFormat: string
{
    case DATE = 'date';
    case DATE_TIME = 'date-time';
    case DURATION = 'duration';
    case EMAIL = 'email';
    case HOSTNAME = 'hostname';
    case IPV4 = 'ipv4';
    case IPV6 = 'ipv6';
    case PHONE = 'phone';
    case REGEX = 'regex';
    case TIME = 'time';
    case URL = 'url';
    case UUID = 'uuid';

    // Non Json Schema Standard
    case MARKDOWN = 'markdown';
    case HTML = 'html';

    public function jsonSchemaFormat(): ?string
    {
        return match ($this) {
            self::MARKDOWN, self::HTML => null,
            default => $this->value
        };
    }
}
