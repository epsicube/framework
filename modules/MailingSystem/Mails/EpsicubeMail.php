<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Mails;

use EpsicubeModules\MailingSystem\Facades\Templates;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class EpsicubeMail extends Mailable
{
    use Queueable, SerializesModels;

    protected string $templateIdentifier = '_blank';

    protected array $customHeaders = [];

    public function setTemplate(string $templateIdentifier): static
    {
        $this->templateIdentifier = $templateIdentifier;

        return $this;
    }

    public function setData(array $data = []): static
    {
        $this->viewData = $data;

        return $this;
    }

    public function pushData(array $data = []): static
    {
        $this->viewData ??= [];
        $this->viewData = array_merge($this->viewData, $data);

        return $this;
    }

    public function pushHeader(string $key, string $value): static
    {
        $this->customHeaders[$key] = $value;

        return $this;
    }

    public function content(): Content
    {
        $template = Templates::get($this->templateIdentifier);

        return $template->content($this->buildViewData());
    }

    public function headers(): Headers
    {
        return new Headers(
            text: $this->customHeaders,
        );
    }
}
