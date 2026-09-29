<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Mails\Drivers;

use Epsicube\Schemas\Properties\IntegerProperty;
use Epsicube\Schemas\Properties\StringProperty;
use Epsicube\Schemas\Schema;
use EpsicubeModules\MailingSystem\Contracts\Driver;
use EpsicubeModules\MailingSystem\Models\Outbox;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;

class SmtpDriver implements Driver
{
    public function identifier(): string
    {
        return 'smtp';
    }

    public function label(): string
    {
        return __('SMTP');
    }

    public function inputSchema(Schema $schema): void
    {
        $schema->append([
            'scheme' => StringProperty::make()
                ->title(__('Scheme'))
                ->nullable()->optional()->default(null)
                ->description(__('Leave empty to let the transport decide (e.g. "smtp" or "smtps")')),
            'url' => StringProperty::make()
                ->title(__('URL'))
                ->nullable()->optional()->default(null)
                ->description(__('Optional DSN-like URL, overrides the other connection fields when provided')),
            'host' => StringProperty::make()
                ->title(__('Host'))
                ->default('127.0.0.1'),
            'port' => IntegerProperty::make()
                ->title(__('Port'))
                ->minimum(1)
                ->maximum(65535)
                ->default(25),
            'username' => StringProperty::make()
                ->title(__('Username'))
                ->nullable()->optional()->default(null),
            'password' => StringProperty::make()
                ->title(__('Password'))
                ->nullable()->optional()->default(null),
            'timeout' => IntegerProperty::make()
                ->title(__('Timeout'))
                ->minimum(1)
                ->nullable()->optional()->default(null)
                ->description(__('Connection timeout in seconds')),
            'local_domain' => StringProperty::make()
                ->title(__('Local domain'))
                ->nullable()->optional()->default(null)
                ->description(__('Domain used for the EHLO command')),
        ]);
    }

    public function build(array $configuration = []): Mailer
    {
        return Mail::build([
            'transport' => 'smtp',
            ...array_filter(
                Arr::only($configuration, ['scheme', 'url', 'host', 'port', 'username', 'password', 'timeout', 'local_domain']),
                fn (mixed $value) => $value !== null,
            ),
        ]);
    }

    public function configureMail(Email $email, Outbox $model): void
    {
        $email->getHeaders()->addTextHeader('X-Internal-ID', (string) $model->id);
    }

    public function handleResponse(SentMessage $sentMessage, Outbox $outbox): void {}
}
