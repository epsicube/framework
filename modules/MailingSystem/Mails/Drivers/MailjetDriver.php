<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Mails\Drivers;

use DateTimeImmutable;
use Epsicube\Schemas\Properties\BooleanProperty;
use Epsicube\Schemas\Properties\StringProperty;
use Epsicube\Schemas\Schema;
use EpsicubeModules\MailingSystem\Contracts\Driver;
use EpsicubeModules\MailingSystem\Contracts\HasWebhooks;
use EpsicubeModules\MailingSystem\Enums\MessageEngagement;
use EpsicubeModules\MailingSystem\Enums\MessageStatus;
use EpsicubeModules\MailingSystem\Enums\MessageType;
use EpsicubeModules\MailingSystem\Events\MessageDeliveryEvent;
use EpsicubeModules\MailingSystem\Events\MessageEngagementEvent;
use EpsicubeModules\MailingSystem\Integrations\Administration\Contracts\HasMailerAdministrationPanel;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Schemas\DriverAdministration\MailjetAdministrationPanel;
use EpsicubeModules\MailingSystem\Mails\Drivers\Mailjet\MailjetSentMessage;
use EpsicubeModules\MailingSystem\Models\Outbox;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;

class MailjetDriver implements Driver, HasMailerAdministrationPanel, HasWebhooks
{
    public const array WEBHOOK_EVENTS = ['sent', 'open', 'click', 'bounce', 'blocked', 'spam', 'unsub'];

    public static function configureDriverPanel(\Filament\Schemas\Schema $schema, array $configuration = []): \Filament\Schemas\Schema
    {
        return MailjetAdministrationPanel::configure($schema, $configuration);
    }

    public function identifier(): string
    {
        return 'mailjet';
    }

    public function label(): string
    {
        return __('Mailjet');
    }

    public function inputSchema(Schema $schema): void
    {
        $schema->append([
            'public_key'  => StringProperty::make()->title(__('Public key')),
            'private_key' => StringProperty::make()->title(__('Private key')),
            'sandbox'     => BooleanProperty::make()->title(__('Run in sandbox'))->optional()->default(false),
        ]);
    }

    public function build(array $configuration = []): Mailer
    {
        return Mail::build([
            'transport'   => 'mailjet+api',
            'public_key'  => $configuration['public_key'],
            'private_key' => $configuration['private_key'],
            'sandbox'     => $configuration['sandbox'] ?? false,
        ]);
    }

    public function configureMail(Email $email, Outbox $model): void
    {
        $email->getHeaders()->addTextHeader('X-MJ-CustomID', (string) $model->id);

        // TODO per-mail config
        $email->getHeaders()->addTextHeader('X-Mailjet-TrackOpen', '1');
        $email->getHeaders()->addTextHeader('X-Mailjet-TrackClick', '1');
    }

    public function handleResponse(SentMessage $sentMessage, Outbox $outbox): void
    {
        if (! ($sentMessage instanceof MailjetSentMessage)) {
            return;
        }

        $result = $sentMessage->getResult();
        $messageResponse = data_get($result, 'Messages.0');

        // Ensure response CustomID are same as outbox
        if ((string) data_get($messageResponse, 'CustomID') !== (string) $outbox->id) {
            return;
        }
        DB::transaction(function () use (&$outbox, &$messageResponse) {
            foreach (['To', 'Cc', 'Bcc'] as $type) {
                $recipients = data_get($messageResponse, $type, []);
                foreach ($recipients as $message) {
                    if (! empty($email = data_get($message, 'Email'))) {
                        $outbox->messages()
                            ->where('recipient', $email)
                            ->where('type', MessageType::from(mb_strtolower($type)))
                            ->update([
                                'meta->Mailjet' => Arr::only($message, ['MessageID', 'MessageUUID']),
                                'status'        => MessageStatus::DEFERRED,
                            ]);
                    }
                }
            }
        });

    }

    public function parseWebhookEvent(Request $request): array
    {
        $payloads = $request->all();
        // Support both V1 and V2
        if (is_array($payloads) && ! array_is_list($payloads)) {
            $payloads = [$payloads];
        }

        return array_filter(array_map(function (array $payload) {
            if (($outboxId = $payload['CustomID'] ?? null) === null) {
                return null;
            }

            if (! $time = DateTimeImmutable::createFromFormat('U', (string) $payload['time'])) {
                throw new RuntimeException(sprintf('Invalid date "%s".', $payload['time']));
            }
            if (empty($recipientEmail = $payload['email'] ?? null)) {
                throw new RuntimeException('Cannot parse email from payload.');
            }

            if (in_array($payload['event'], ['bounce', 'sent', 'blocked'], true)) {
                return new MessageDeliveryEvent(
                    outboxId: $outboxId,
                    recipientEmail: $recipientEmail,
                    status: match ($payload['event']) {
                        'bounce'  => MessageStatus::BOUNCED,
                        'sent'    => MessageStatus::DELIVERED,
                        'blocked' => MessageStatus::DROPPED,
                    },
                    time: $time,
                    reason: $payload['smtp_reply'] ?? $payload['error_related_to'] ?? ''
                );
            }

            return new MessageEngagementEvent(
                outboxId: $outboxId,
                recipientEmail: $recipientEmail,
                engagement: match ($payload['event']) {
                    'click' => MessageEngagement::CLICKED,
                    'open'  => MessageEngagement::OPENED,
                    'spam'  => MessageEngagement::SPAM,
                    'unsub' => MessageEngagement::UNSUBSCRIBED,
                    default => throw new RuntimeException(sprintf('Unsupported event "%s".', $payload['event'])),
                },
                time: $time
            );
        }, $payloads));
    }
}
