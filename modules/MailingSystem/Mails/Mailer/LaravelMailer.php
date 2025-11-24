<?php

declare(strict_types=1);

namespace UniGaleModules\MailingSystem\Mails\Mailer;

use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Mail;
use UniGaleModules\MailingSystem\Contracts\Mailer as MailerContract;

class LaravelMailer implements MailerContract
{
    public function __construct(public string $identifier) {}

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function label(): string
    {
        return __('Internal: :mailer', ['mailer' => $this->identifier]);
    }

    public function mailer(): Mailer
    {
        return Mail::mailer($this->identifier);
    }
}
