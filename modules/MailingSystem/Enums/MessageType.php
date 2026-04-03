<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Enums;

enum MessageType: string
{
    case TO = 'to';
    case CC = 'cc';
    case BCC = 'bcc';

    public function label(): string
    {
        return match ($this) {
            self::TO  => __('To'),
            self::CC  => __('Cc'),
            self::BCC => __('Bcc'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::TO  => __('Primary recipient of the email.'),
            self::CC  => __('Carbon Copy: Recipient visible to others, receiving a copy for information.'),
            self::BCC => __('Blind Carbon Copy: Recipient hidden from others, receiving a copy discreetly.'),
        };
    }
}
