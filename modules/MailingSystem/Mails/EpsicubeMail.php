<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Mails;

use EpsicubeModules\MailingSystem\Facades\Mailers;
use EpsicubeModules\MailingSystem\Facades\Templates;
use EpsicubeModules\MailingSystem\Models\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Mail\SentMessage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

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
        return new Headers(text: $this->customHeaders);
    }

    /* Envelope */
    public function send($mailer = null): ?SentMessage
    {
        $mailer = Mailers::get($mailer ?? $this->mailer ?? config('mail.default'));

        // MAil Database instance
        /**
         * $uid=Str::uuid7();
         * $maiModel=new MAilModel(['guid'=>$uid]);
         *
         * foreach recipients -> $mailModel->addRecipeint()
         * {
         *
         * }
         *
         **/

        $mailModel = new Mail(['uuid' => Str::uuid7()->toString()]);
        $mailer->configureMail($this, $mailModel);

        try {
            $result = parent::send($mailer->mailer());
//            $mailModel->fill([
//                'status'   => 'sent',
//                'retry_at' => '...'
//            ]);
            return $result;
        } catch (\Throwable $e) {
//            $mailModel->fill([
//                'status'   => 'failed',
//                'retry_at' => '...'
//            ]);
            return null;
        } finally {
            // $mailModel->save();
        }
    }
}
