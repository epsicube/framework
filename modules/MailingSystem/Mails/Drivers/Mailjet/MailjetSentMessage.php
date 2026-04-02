<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Mails\Drivers\Mailjet;

use Symfony\Component\Mailer\SentMessage;

class MailjetSentMessage extends SentMessage
{
    protected ?array $payload = null;

    protected ?array $result = null;

    public function setPayload(array $data): void
    {
        $this->payload = $data;
    }

    public function setResult(array $data): void
    {
        $this->result = $data;
    }

    public function getPayload(): ?array
    {
        return $this->payload;
    }

    public function getResult(): ?array
    {
        return $this->result;
    }
}
