<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Mails\Drivers;

use Epsicube\Schemas\Properties\BooleanProperty;
use Epsicube\Schemas\Properties\StringProperty;
use Epsicube\Schemas\Schema;
use EpsicubeModules\MailingSystem\Contracts\Driver;
use EpsicubeModules\MailingSystem\Models\Outbox;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;

class MailjetDriver implements Driver
{
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
            'track_open'  => BooleanProperty::make()->title(__('Track Open'))->optional()->default(false),
            'track_click' => BooleanProperty::make()->title(__('Track Click'))->optional()->default(false),
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
        $email->getHeaders()->addTextHeader('X-MJ-CustomID', $model->internal_id);

        // TODO per-mail config
        return;
        if ($this->configuration['track_open'] ?? false) {
            $email->getHeaders()->addTextHeader('X-Mailjet-TrackOpen', '1');
        }

        if ($this->configuration['track_click'] ?? false) {
            $email->getHeaders()->addTextHeader('X-Mailjet-TrackClick', '1');
        }
    }
}
