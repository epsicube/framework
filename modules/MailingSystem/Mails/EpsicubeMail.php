<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Mails;

use EpsicubeModules\MailingSystem\Facades\Mailers;
use EpsicubeModules\MailingSystem\Facades\Templates;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\SentMessage;
use Illuminate\Queue\SerializesModels;

class EpsicubeMail extends Mailable
{
    use Queueable, SerializesModels;

    protected string $templateIdentifier = '_blank';

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

    public function content(): Content
    {
        $template = Templates::get($this->templateIdentifier);

        return $template->content($this->buildViewData());
    }

    /* Envelope */
    public function send($mailer = null): ?SentMessage
    {
        $mailer = Mailers::get($mailer ?? $this->mailer ?? config('mail.default'))->mailer();

        return parent::send($mailer);
    }
}
