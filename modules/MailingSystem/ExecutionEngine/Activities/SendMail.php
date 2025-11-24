<?php

declare(strict_types=1);

namespace UniGaleModules\MailingSystem\ExecutionEngine\Activities;

use UniGaleModules\ExecutionPlatform\Contracts\Activity;
use UniGaleModules\MailingSystem\Mails\UnigaleMail;

class SendMail implements Activity
{
    /**
     * {@inheritDoc}
     */
    public function identifier(): string
    {
        return 'unigale-mail::send-mail';
    }

    public function label(): string
    {
        return __('Send Mail');
    }

    public static function make(): static
    {
        return new static;
    }

    /**
     * @param  array{mailer: string, template: string, subject: string, to: list<string>, cc: list<string>, bcc: list<string>, data: array<string,mixed>}  $inputs
     * @return array{messageId: string}
     */
    public function handle(array $inputs = []): array
    {
        $mail = UnigaleMail::make()
            ->mailer(data_get($inputs, 'mailer'))
            ->setTemplate(data_get($inputs, 'template'))
            ->subject(data_get($inputs, 'subject'))
            ->to(data_get($inputs, 'to'))
            ->cc(data_get($inputs, 'cc'))
            ->bcc(data_get($inputs, 'bcc'))
            ->with(data_get($inputs, 'template_configuration'));

        $message = $mail->send();

        return ['messageId' => $message->getMessageId()];
    }
}
