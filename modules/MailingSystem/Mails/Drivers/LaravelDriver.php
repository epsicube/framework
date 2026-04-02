<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Mails\Drivers;

use Epsicube\Schemas\Properties\EnumProperty;
use Epsicube\Schemas\Schema;
use Epsicube\Schemas\Types\EnumCase;
use EpsicubeModules\MailingSystem\Contracts\Driver;
use EpsicubeModules\MailingSystem\Mails\Drivers\Mailjet\MailjetSentMessage;
use EpsicubeModules\MailingSystem\Models\Outbox;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;

class LaravelDriver implements Driver
{
    public function identifier(): string
    {
        return 'laravel';
    }

    public function label(): string
    {
        return __('Laravel');
    }

    public function inputSchema(Schema $schema): void
    {
        $schema->append([
            'name' => EnumProperty::make()
                ->title(__('Name'))
                ->cases(...array_map(fn(string $name) => EnumCase::make($name), array_keys(config('mail.mailers'))))
                ->nullable()->optional()->default(null)
                ->description(__('Leave empty to use default Laravel driver')),
        ]);
    }

    public function build(array $configuration = []): Mailer
    {
        return Mail::mailer($configuration['name'] ?? null);
    }

    public function configureMail(Email $email, Outbox $model): void
    {
        $email->getHeaders()->addTextHeader('X-Internal-ID', $model->internal_id);
    }

    public function handleResponse(SentMessage $sentMessage, Outbox $outbox): void
    {
    }
}
