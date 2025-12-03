<?php

declare(strict_types=1);

namespace UniGaleModules\MailingSystem\Mails\Templates;

use Filament\Forms\Components\Textarea;
use Illuminate\Mail\Mailables\Content;
use UniGaleModules\MailingSystem\Contracts\HasInputSchema;
use UniGaleModules\MailingSystem\Contracts\MailTemplate;

class Html implements HasInputSchema, MailTemplate
{
    public function identifier(): string
    {
        return '_html';
    }

    public function label(): string
    {
        return __('HTML');
    }

    public function content(array $with = []): Content
    {
        return new Content(
            view: 'unigale-mail::html',
            with: $with,
        );
    }

    public function inputSchema(): array
    {
        return [
            Textarea::make('content')->label(__('Content'))->required()->autosize(),
        ];
    }
}
