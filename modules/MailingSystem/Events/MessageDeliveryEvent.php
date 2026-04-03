<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Events;

use DateTimeInterface;
use EpsicubeModules\MailingSystem\Enums\MessageStatus;

class MessageDeliveryEvent
{
    public function __construct(
        protected readonly string $outboxId,
        public readonly string $recipientEmail,
        protected readonly MessageStatus $status,
        protected DateTimeInterface $time,
        protected readonly string $reason = '',
    ) {}

    public function getOutboxId(): string
    {
        return $this->outboxId;
    }

    public function getRecipientEmail(): string
    {
        return $this->recipientEmail;
    }

    public function getStatus(): MessageStatus
    {
        return $this->status;
    }

    public function getTime(): DateTimeInterface
    {
        return $this->time;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
