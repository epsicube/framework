<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Enums;

enum MessageEngagement: string
{
    case OPENED = 'opened';
    case CLICKED = 'clicked';
    case SPAM = 'spam';
    case UNSUBSCRIBED = 'unsubscribed';

    public function label(): string
    {
        return match ($this) {
            self::OPENED       => __('Opened'),
            self::CLICKED      => __('Clicked'),
            self::SPAM         => __('Spam'),
            self::UNSUBSCRIBED => __('Unsubscribed'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::OPENED       => __('The recipient has opened the email.'),
            self::CLICKED      => __('The recipient has clicked on a link within the email.'),
            self::SPAM         => __('The recipient has marked this email as spam.'),
            self::UNSUBSCRIBED => __('The recipient has clicked the unsubscribe link.'),
        };
    }

    public function priority(): int
    {
        return match ($this) {
            self::SPAM         => 100,
            self::UNSUBSCRIBED => 80,
            self::CLICKED      => 60,
            self::OPENED       => 40,
        };
    }
}
