<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\ExecutionPlatform\Activities;

use Epsicube\Schemas\Enums\StringFormat;
use Epsicube\Schemas\Properties\ArrayProperty;
use Epsicube\Schemas\Properties\EnumProperty;
use Epsicube\Schemas\Properties\ObjectProperty;
use Epsicube\Schemas\Properties\StringProperty;
use Epsicube\Schemas\Schema;
use Epsicube\Schemas\Types\EnumCase;
use EpsicubeModules\ExecutionPlatform\Contracts\Activity;
use EpsicubeModules\MailingSystem\Contracts\Mailer;
use EpsicubeModules\MailingSystem\Contracts\MailTemplate;
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
            'mailer' => EnumProperty::make()
                ->title('Mailer identifier')
                ->dynamic(fn () => collect(Mailers::all())
                    ->map(static fn (Mailer $mailer, string $identifier) => new EnumCase($identifier, $mailer->label()))
                    ->values()->all()
                ),
            'template' => EnumProperty::make()
                ->title('Template Identifier')
                ->dynamic(fn () => collect(Templates::all())
                    ->map(static fn (MailTemplate $template, string $identifier) => new EnumCase($identifier, $template->label()))
                    ->values()->all()
                ),
            'subject' => StringProperty::make()->title('Subject')->minLength(2),

            'to' => ArrayProperty::make()->items(
                StringProperty::make()->format(StringFormat::EMAIL),
            )->minItems(1)->title('To'),

            'cc' => ArrayProperty::make()->items(
                StringProperty::make()->format(StringFormat::EMAIL),
            )->title('CC')->optional(),

            'bcc' => ArrayProperty::make()->items(
                StringProperty::make()->format(StringFormat::EMAIL),
            )->title('BCC')->optional(),

            // TODO dynamique (oneOf, or relational schema)
            'template_configuration' => ObjectProperty::make()
                ->title('Template configuration')
                ->description("Each template has its own configuration; refer to the template's specific configuration.")
                ->additionalProperties(true),
        ]);
    }

    public function handle(array $inputs = []): array
    {
        $mail = (new EpsicubeMail)
            ->mailer(data_get($inputs, 'mailer'))
            ->setTemplate(data_get($inputs, 'template'))
            ->subject(data_get($inputs, 'subject'))
            ->to(data_get($inputs, 'to', []))
            ->cc(data_get($inputs, 'cc', []))
            ->bcc(data_get($inputs, 'bcc', []))
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
