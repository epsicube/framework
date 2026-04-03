<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Events;

use DateTimeInterface;
use EpsicubeModules\MailingSystem\Enums\MessageEngagement;

class MessageEngagementEvent
{
    public function __construct(
        protected readonly string $outboxId,
        public readonly string $recipientEmail,
        protected readonly MessageEngagement $engagement,
        protected DateTimeInterface $time,
    ) {}

    public function getOutboxId(): string
    {
        return $this->outboxId;
    }

    public function getRecipientEmail(): string
    {
        return $this->recipientEmail;
    }

    public function getEngagement(): MessageEngagement
    {
        return $this->engagement;
    }

    public function getTime(): DateTimeInterface
    {
        return $this->time;
    }
}
