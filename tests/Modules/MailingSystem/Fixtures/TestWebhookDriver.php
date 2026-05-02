<?php

declare(strict_types=1);

namespace Epsicube\Tests\Modules\MailingSystem\Fixtures;

use DateTimeImmutable;
use Epsicube\Schemas\Schema;
use EpsicubeModules\MailingSystem\Contracts\Driver;
use EpsicubeModules\MailingSystem\Contracts\HasWebhooks;
use EpsicubeModules\MailingSystem\Enums\MessageStatus;
use EpsicubeModules\MailingSystem\Events\MessageDeliveryEvent;
use EpsicubeModules\MailingSystem\Models\Outbox;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;

class TestWebhookDriver implements Driver, HasWebhooks
{
    public function identifier(): string
    {
        return 'tests-webhook';
    }

    public function label(): string
    {
        return 'Tests Webhook';
    }

    public function inputSchema(Schema $schema): void {}

    public function build(array $configuration = []): Mailer
    {
        return Mail::mailer('array');
    }

    public function configureMail(Email $email, Outbox $model): void {}

    public function handleResponse(SentMessage $sentMessage, Outbox $outbox): void {}

    public function parseWebhookEvent(Request $request): array
    {
        return [
            new MessageDeliveryEvent(
                outboxId: 'outbox-1',
                recipientEmail: 'john@example.test',
                status: MessageStatus::DELIVERED,
                time: new DateTimeImmutable('2026-05-02T10:00:00+00:00'),
            ),
        ];
    }
}
