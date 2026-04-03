<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Enums;

enum MessageStatus: string
{
    case RECEIVED = 'received';

    case DELIVERED = 'delivered';
    case DEFERRED = 'deferred';
    case BOUNCED = 'bounced';
    case DROPPED = 'dropped';

    public function label(): string
    {
        return match ($this) {
            self::RECEIVED  => __('Received'),
            self::DELIVERED => __('Delivered'),
            self::DEFERRED  => __('Deferred'),
            self::BOUNCED   => __('Bounced'),
            self::DROPPED   => __('Dropped'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::RECEIVED  => __('The message has been received by the system.'),
            self::DELIVERED => __('The message has been successfully delivered to the recipient\'s.'),
            self::DEFERRED  => __('The delivery is temporarily delayed; the provider will try again.'),
            self::BOUNCED   => __('The message could not be delivered due to a permanent error (e.g., invalid address).'),
            self::DROPPED   => __('The message was blocked or dropped (e.g., marked as spam or blacklisted).'),
        };
    }

    public function priority(): int
    {
        return match ($this) {
            self::BOUNCED, self::DROPPED => 40,
            self::DELIVERED => 30,
            self::DEFERRED  => 20,
            self::RECEIVED  => 10,
        };
    }
}
