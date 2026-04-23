<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Mails\Drivers;

use DateTimeImmutable;
use Epsicube\Schemas\Properties\BooleanProperty;
use Epsicube\Schemas\Properties\IntegerProperty;
use Epsicube\Schemas\Properties\StringProperty;
use Epsicube\Schemas\Schema;
use EpsicubeModules\MailingSystem\Contracts\Driver;
use EpsicubeModules\MailingSystem\Contracts\HasWebhooks;
use EpsicubeModules\MailingSystem\Enums\MessageEngagement;
use EpsicubeModules\MailingSystem\Enums\MessageStatus;
use EpsicubeModules\MailingSystem\Events\MessageDeliveryEvent;
use EpsicubeModules\MailingSystem\Events\MessageEngagementEvent;
use EpsicubeModules\MailingSystem\Models\Outbox;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use JsonException;
use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;

class SendGridDriver implements Driver, HasWebhooks
{
    public function identifier(): string
    {
        return 'sendgrid';
    }

    public function label(): string
    {
        return __('SendGrid');
    }

    public function inputSchema(Schema $schema): void
    {
        $schema->append([
            'api_key' => StringProperty::make()
                ->title(__('API key'))
                ->minLength(20),
            'host' => StringProperty::make()
                ->title(__('SMTP host'))
                ->optional()
                ->default('smtp.sendgrid.net'),
            'port' => IntegerProperty::make()
                ->title(__('SMTP port'))
                ->minimum(1)
                ->maximum(65535)
                ->optional()
                ->default(587),
            'scheme' => StringProperty::make()
                ->title(__('Encryption'))
                ->optional()
                ->nullable()
                ->default('tls')
                ->description(__('Use `tls`, `smtps`, or leave empty if your relay requires no encryption.')),
            'click_tracking' => BooleanProperty::make()
                ->title(__('Enable click tracking'))
                ->optional()
                ->default(true),
            'open_tracking' => BooleanProperty::make()
                ->title(__('Enable open tracking'))
                ->optional()
                ->default(true),
            'subscription_tracking' => BooleanProperty::make()
                ->title(__('Enable subscription tracking'))
                ->optional()
                ->default(false),
            'category' => StringProperty::make()
                ->title(__('Category'))
                ->optional()
                ->nullable()
                ->description(__('Optional SendGrid category attached to every message sent with this mailer.')),
            'asm_group_id' => IntegerProperty::make()
                ->title(__('ASM group ID'))
                ->optional()
                ->nullable()
                ->minimum(1)
                ->description(__('Optional unsubscribe group used for subscription management.')),
            'ip_pool' => StringProperty::make()
                ->title(__('IP pool'))
                ->optional()
                ->nullable()
                ->description(__('Optional SendGrid IP pool name.')),
        ]);
    }

    public function build(array $configuration = []): Mailer
    {
        return Mail::build([
            'transport' => 'smtp',
            'host'      => $configuration['host'] ?? 'smtp.sendgrid.net',
            'port'      => $configuration['port'] ?? 587,
            'scheme'    => blank($configuration['scheme'] ?? 'tls') ? null : $configuration['scheme'],
            'username'  => 'apikey',
            'password'  => $configuration['api_key'],
        ]);
    }

    public function configureMail(Email $email, Outbox $model): void
    {
        $configuration = $model->mailer->configuration ?? [];

        $payload = $this->parseSmtpApiHeader($email);
        $payload['unique_args'] = array_merge($payload['unique_args'] ?? [], [
            'outbox_id' => (string) $model->id,
        ]);

        if (! empty($configuration['category'])) {
            $payload['category'] = $configuration['category'];
        }

        if (! empty($configuration['asm_group_id'])) {
            $payload['asm_group_id'] = $configuration['asm_group_id'];
        }

        if (! empty($configuration['ip_pool'])) {
            $payload['ip_pool'] = $configuration['ip_pool'];
        }

        $payload['filters'] = array_replace_recursive($payload['filters'] ?? [], array_filter([
            'clicktrack' => ($configuration['click_tracking'] ?? true)
                ? ['settings' => ['enable' => 1, 'enable_text' => true]]
                : ['settings' => ['enable' => 0]],
            'opentrack' => ($configuration['open_tracking'] ?? true)
                ? ['settings' => ['enable' => 1]]
                : ['settings' => ['enable' => 0]],
            'subscriptiontrack' => ($configuration['subscription_tracking'] ?? false)
                ? ['settings' => ['enable' => 1]]
                : null,
        ]));

        $email->getHeaders()->remove('X-SMTPAPI');
        $email->getHeaders()->addTextHeader('X-SMTPAPI', $this->encodeSmtpApiHeader($payload));
    }

    public function handleResponse(SentMessage $sentMessage, Outbox $outbox): void {}

    public function parseWebhookEvent(Request $request): array
    {
        $payloads = $request->all();
        if (is_array($payloads) && ! array_is_list($payloads)) {
            $payloads = [$payloads];
        }

        return array_values(array_filter(array_map(function (array $payload) {
            $outboxId = data_get($payload, 'unique_args.outbox_id');
            if (blank($outboxId)) {
                return null;
            }

            $timestamp = $payload['timestamp'] ?? null;
            if (! is_numeric($timestamp) || ! $time = DateTimeImmutable::createFromFormat('U', (string) $timestamp)) {
                throw new RuntimeException(sprintf('Invalid SendGrid timestamp "%s".', (string) $timestamp));
            }

            $recipientEmail = $payload['email'] ?? null;
            if (blank($recipientEmail)) {
                throw new RuntimeException('Cannot parse recipient email from SendGrid payload.');
            }

            return match ($payload['event'] ?? null) {
                'processed' => new MessageDeliveryEvent(
                    outboxId: (string) $outboxId,
                    recipientEmail: $recipientEmail,
                    status: MessageStatus::RECEIVED,
                    time: $time,
                ),
                'delivered' => new MessageDeliveryEvent(
                    outboxId: (string) $outboxId,
                    recipientEmail: $recipientEmail,
                    status: MessageStatus::DELIVERED,
                    time: $time,
                ),
                'deferred' => new MessageDeliveryEvent(
                    outboxId: (string) $outboxId,
                    recipientEmail: $recipientEmail,
                    status: MessageStatus::DEFERRED,
                    time: $time,
                    reason: (string) ($payload['response'] ?? ''),
                ),
                'bounce' => new MessageDeliveryEvent(
                    outboxId: (string) $outboxId,
                    recipientEmail: $recipientEmail,
                    status: MessageStatus::BOUNCED,
                    time: $time,
                    reason: (string) ($payload['reason'] ?? $payload['response'] ?? ''),
                ),
                'dropped' => new MessageDeliveryEvent(
                    outboxId: (string) $outboxId,
                    recipientEmail: $recipientEmail,
                    status: MessageStatus::DROPPED,
                    time: $time,
                    reason: (string) ($payload['reason'] ?? ''),
                ),
                'open' => new MessageEngagementEvent(
                    outboxId: (string) $outboxId,
                    recipientEmail: $recipientEmail,
                    engagement: MessageEngagement::OPENED,
                    time: $time,
                ),
                'click' => new MessageEngagementEvent(
                    outboxId: (string) $outboxId,
                    recipientEmail: $recipientEmail,
                    engagement: MessageEngagement::CLICKED,
                    time: $time,
                ),
                'spamreport', 'spam_report', 'spam report' => new MessageEngagementEvent(
                    outboxId: (string) $outboxId,
                    recipientEmail: $recipientEmail,
                    engagement: MessageEngagement::SPAM,
                    time: $time,
                ),
                'unsubscribe', 'group_unsubscribe' => new MessageEngagementEvent(
                    outboxId: (string) $outboxId,
                    recipientEmail: $recipientEmail,
                    engagement: MessageEngagement::UNSUBSCRIBED,
                    time: $time,
                ),
                default => null,
            };
        }, $payloads)));
    }

    protected function parseSmtpApiHeader(Email $email): array
    {
        $rawHeader = $email->getHeaders()->get('X-SMTPAPI')?->getBodyAsString();
        if (blank($rawHeader)) {
            return [];
        }

        try {
            $payload = json_decode($rawHeader, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('Invalid existing X-SMTPAPI header.', previous: $e);
        }

        if (! is_array($payload)) {
            throw new RuntimeException('X-SMTPAPI header must decode to an object.');
        }

        if (isset($payload['unique_args']) && ! is_array($payload['unique_args'])) {
            throw new RuntimeException('X-SMTPAPI unique_args must be an object.');
        }

        return $payload;
    }

    protected function encodeSmtpApiHeader(array $payload): string
    {
        if (isset($payload['unique_args']) && is_array($payload['unique_args'])) {
            $payload['unique_args'] = Arr::map($payload['unique_args'], static fn (mixed $value) => (string) $value);
        }

        try {
            return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $e) {
            throw new RuntimeException('Unable to encode SendGrid X-SMTPAPI header.', previous: $e);
        }
    }
}
