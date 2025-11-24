<?php

declare(strict_types=1);

namespace UniGaleModules\MailingSystem\Mails\Templates;

use Filament\Forms\Components\Textarea;
use Illuminate\Mail\Mailables\Content;
use UniGaleModules\MailingSystem\Contracts\HasInputSchema;
use UniGaleModules\MailingSystem\Contracts\MailTemplate;

class Blank implements HasInputSchema, MailTemplate
{
    public function identifier(): string
    {
        return '_blank';
    }

    public function label(): string
    {
        return __('Blank');
    }

    public function content(array $with = []): Content
    {
        return new Content(
            view: 'unigale-mail::blank',
            with: $with,
        );
    }

    public function inputSchema(): array
    {
        return [
            Textarea::make('content')
                ->label(__('Content'))->required()->autosize(),
        ];
    }
}
