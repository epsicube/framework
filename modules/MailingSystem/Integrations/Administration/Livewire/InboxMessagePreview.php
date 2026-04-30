<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Livewire;

use DirectoryTree\ImapEngine\Connection\Responses\Data\ListData;
use DirectoryTree\ImapEngine\Enums\ImapFetchIdentifier;
use EpsicubeModules\MailingSystem\Models\InboxAccount;
use EpsicubeModules\MailingSystem\Services\InboxConnector;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use PhpMimeMailParser\Parser;
use Throwable;

#[Lazy(isolate: true)]
class InboxMessagePreview extends Component
{
    protected const int MESSAGE_CACHE_SECONDS = 300;

    public int $accountId;

    public int $messageUid;

    public function placeholder(array $params = []): View
    {
        return view('epsicube-mail::filament.livewire.inbox-message-preview-placeholder');
    }

    public function render(): View
    {
        return view('epsicube-mail::filament.livewire.inbox-message-preview', [
            'selected' => $this->messageDetails(),
        ]);
    }

    protected function messageDetails(): array
    {
        try {
            $account = InboxAccount::query()
                ->where('is_active', true)
                ->findOrFail($this->accountId);

            $rawMessage = $this->fetchMessageEml($account);

            if (blank($rawMessage)) {
                return [
                    'error' => __('Message not found.'),
                ];
            }

            $parser = new Parser;
            $parser->setText($rawMessage);

            $text = $parser->getMessageBody();

            if (blank($text)) {
                $text = $this->htmlToReadableText($parser->getMessageBody('html') ?? '');
            }

            return [
                'subject'     => $parser->getHeader('subject') ?: __('No subject'),
                'from'        => $parser->getHeader('from') ?: __('Unknown sender'),
                'date'        => $parser->getHeader('date'),
                'body'        => $text ?: __('No readable content.'),
                'raw_message' => $rawMessage,
                'error'       => null,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'subject'     => __('Unable to load message'),
                'from'        => null,
                'date'        => null,
                'body'        => $e->getMessage(),
                'raw_message' => null,
                'error'       => $e->getMessage(),
            ];
        }
    }

    protected function fetchMessageEml(InboxAccount $account): ?string
    {
        return Cache::remember(
            $this->messageCacheKey($account),
            now()->addSeconds(self::MESSAGE_CACHE_SECONDS),
            function () use ($account): ?string {
                $folder = app(InboxConnector::class)->folder($account);

                // Select the folder before issuing a raw RFC822 fetch.
                $folder->messages();

                $response = $folder
                    ->mailbox()
                    ->connection()
                    ->fetch('RFC822', $this->messageUid, identifier: ImapFetchIdentifier::Uid)
                    ->first();

                $data = $response?->tokenAt(3);

                if (! $data instanceof ListData) {
                    return null;
                }

                $rawMessage = $data->lookup('RFC822')?->value;

                return is_string($rawMessage) && $rawMessage !== '' ? $rawMessage : null;
            }
        );
    }

    protected function messageCacheKey(InboxAccount $account): string
    {
        return implode(':', [
            'mailing-system',
            'inbox',
            'eml',
            $account->id,
            (string) $account->updated_at?->timestamp,
            $this->messageUid,
        ]);
    }

    protected function htmlToReadableText(string $html): string
    {
        $html = preg_replace('/<(style|script|head)\b[^>]*>.*?<\/\1>/is', '', $html) ?? $html;
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
        $html = preg_replace('/<\/(p|div|tr|li|h[1-6])>/i', "\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5);
        $text = preg_replace("/[ \t]+\n/", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return mb_trim($text);
    }
}
