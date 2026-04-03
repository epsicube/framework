<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Enums;

enum OutboxStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case ERROR = 'error';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('Pending'),
            self::SENT    => __('Sent'),
            self::ERROR   => __('Error'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PENDING => __('The message batch is queued and waiting to be sent to the provider.'),
            self::SENT    => __('The batch has been successfully accepted by the remote SMTP/API server.'),
            self::ERROR   => __('A connection or protocol error occurred while communicating with the provider.'),
        };
    }
}
