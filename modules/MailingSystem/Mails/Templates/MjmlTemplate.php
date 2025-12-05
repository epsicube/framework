<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Mails\Templates;

use EpsicubeModules\MailingSystem\Contracts\MailTemplate;
use Illuminate\Mail\Mailables\Content;
use Spatie\Mjml\Mjml;

abstract class MjmlTemplate implements MailTemplate
{
    public function content(array $with = []): Content
    {
        $htmlString = str($this->getMjml($with));

        if (! $htmlString->contains('</mj-body>')) {
            $htmlString = str("<mj-body>{$htmlString}</mj-body>");
        }
        if (! $htmlString->contains('</mjml>')) {
            $htmlString = str("<mjml>{$htmlString}</mjml>");
        }
        $mjmlString = Mjml::new()->toHtml($htmlString->toString());

        return new Content(
            htmlString: $mjmlString,
        );
    }

    abstract public function getMjml(array $with = []): string;

    public static function make(): static
    {
        return new static;
    }
}
