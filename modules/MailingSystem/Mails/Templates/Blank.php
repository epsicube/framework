<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Mails\Templates;

use EpsicubeModules\MailingSystem\Contracts\HasInputSchema;
use EpsicubeModules\MailingSystem\Contracts\MailTemplate;
use Filament\Forms\Components\Textarea;
use Illuminate\Mail\Mailables\Content;

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
            view: 'epsicube-mail::blank',
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
