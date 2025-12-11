<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\ExecutionPlatform\Activities;

use Epsicube\Schemas\Enums\StringFormat;
use Epsicube\Schemas\Properties\ArrayProperty;
use Epsicube\Schemas\Properties\ObjectProperty;
use Epsicube\Schemas\Properties\StringProperty;
use Epsicube\Schemas\Schema;
use EpsicubeModules\ExecutionPlatform\Contracts\Activity;
use EpsicubeModules\MailingSystem\Facades\Mailers;
use EpsicubeModules\MailingSystem\Facades\Templates;
use EpsicubeModules\MailingSystem\Mails\EpsicubeMail;

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
    public function inputSchema(Schema $schema): void
    {
        $schema->append([
            'mailer'   => StringProperty::make()->title('Mailer identifier'), // TODO ENUM ->enum(array_keys(Mailers::all()))
            'template' => StringProperty::make()->title('Template Identifier'), // TODO ENUM ->enum(array_keys(Templates::all()))
            'subject'  => StringProperty::make()->title('Subject')->minLength(2),

            'to' => ArrayProperty::make()->items(
                StringProperty::make()->title('To')->format(StringFormat::EMAIL),
            )->minItems(1),

            'cc' => ArrayProperty::make()->items(
                StringProperty::make()->title('CC')->format(StringFormat::EMAIL),
            ),

            'bcc' => ArrayProperty::make()->items(
                StringProperty::make()->title('BCC')->format(StringFormat::EMAIL),
            ),

            // TODO dynamique
            'template_configuration' => ObjectProperty::make()->properties([
                'content' => StringProperty::make(),
            ])->title('Template configuration'),
        ]);
    }

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

    public function outputSchema(Schema $schema): void
    {
        $schema->append([
            'messageId' => StringProperty::make(),
        ]);
    }
}
