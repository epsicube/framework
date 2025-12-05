<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\ExecutionPlatform\Activities;

use EpsicubeModules\ExecutionPlatform\Contracts\Activity;
use EpsicubeModules\MailingSystem\Facades\Mailers;
use EpsicubeModules\MailingSystem\Facades\Templates;
use EpsicubeModules\MailingSystem\Mails\EpsicubeMail;
use Illuminate\JsonSchema\JsonSchema;

class SendMail implements Activity
{
    /**
     * {@inheritDoc}
     */
    public function identifier(): string
    {
        return 'epsicube-mail::send-mail';
    }

    public function label(): string
    {
        return __('Send Mail');
    }

    public function description(): string
    {
        return __('Sends an email using the configured mailer, template, recipients, and contextual data.');
    }

    public static function make(): static
    {
        return new static;
    }

    // TODO custom schema module
    public function inputSchema(): array
    {
        return [
            'mailer'   => JsonSchema::string()->enum(array_keys(Mailers::all()))->required(),
            'template' => JsonSchema::string()->enum(array_keys(Templates::all()))->required(),
            'subject'  => JsonSchema::string()->required(),

            'to' => JsonSchema::array()->items(
                JsonSchema::string()->format('email')->required(),
            ),

            'cc' => JsonSchema::array()->items(
                JsonSchema::string()->format('email')->required(),
            ),

            'bcc' => JsonSchema::array()->items(
                JsonSchema::string()->format('email')->required(),
            ),

            'template_configuration' => JsonSchema::object([
                'content' => JsonSchema::string(),
            ]),
        ];
    }

    /**
     * @param  array{mailer: string, template: string, subject: string, to: list<string>, cc: list<string>, bcc: list<string>, data: array<string,mixed>}  $inputs
     * @return array{messageId: string}
     */
    public function handle(array $inputs = []): array
    {
        $mail = (new EpsicubeMail)
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

    // TODO custom schema module
    public function outputSchema(): array
    {
        return [
            'messageId' => JsonSchema::string()->required(),
        ];
    }
}
