<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Mails\Mailer;

use EpsicubeModules\MailingSystem\Contracts\Mailer as MailerContract;
use EpsicubeModules\MailingSystem\Mails\EpsicubeMail;
use EpsicubeModules\MailingSystem\Models\Mail as MailModel;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Mail;

class MailjetMailer implements MailerContract
{
    public function __construct(
        public string   $identifier,
        //TODO input schema
        protected array $configuration = []
    )
    {
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function label(): string
    {
        return __('Mailjet: :identifier', ['identifier' => $this->identifier]);
    }

    public function mailer(): Mailer
    {
        $smtpConfiguration = [
            'transport'  => 'smtp',
            'host'       => 'in-v3.mailjet.com',
            'port'       => 587,
            'encryption' => 'tls',
            'username'   => $this->configuration['username'],
            'password'   => $this->configuration['password'],
            'timeout'    => null,
        ];


        return Mail::build($smtpConfiguration);
    }

    public function configureMail(EpsicubeMail &$mail, MailModel $model): void
    {
        if (empty($mail->from)) {
            $mail->from(
                $this->configuration['from_address'] ?? config('mail.from.address'),
                $this->configuration['from_name'] ?? config('mail.from.name'),
            );
        }

        $mail->pushHeader('X-MJ-CustomID', (string)$model->uuid);
        $mail->pushHeader("X-Mj-EventPayLoad","Eticket,1234,row,15,seat,B");

        if ($this->configuration['track_open'] ?? false) {
            $mail->pushHeader('X-Mailjet-TrackOpen', '1');
        }

        if ($this->configuration['track_click'] ?? false) {
            $mail->pushHeader('X-Mailjet-TrackClick', '1');
        }
    }
}
