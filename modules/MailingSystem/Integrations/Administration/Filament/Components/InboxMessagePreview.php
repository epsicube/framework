<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Filament\Components;

use DirectoryTree\ImapEngine\Connection\Responses\Data\ListData;
use DirectoryTree\ImapEngine\Enums\ImapFetchIdentifier;
use DirectoryTree\ImapEngine\Enums\ImapSortKey;
use EpsicubeModules\MailingSystem\Integrations\Administration\Support\FormatsMailDates;
use EpsicubeModules\MailingSystem\Models\InboxAccount;
use EpsicubeModules\MailingSystem\Services\InboxConnector;
use Filament\Infolists\Components\Entry;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Support\Facades\Cache;
use Throwable;

class InboxMessagePreview extends Entry
{
    use FormatsMailDates;

    protected const int MESSAGE_CACHE_SECONDS = 300;

    protected const int MESSAGE_WINDOW = 15;

    protected const string PREVIEW_SCHEMA_KEY = 'preview';

    protected string $view = 'epsicube-mail::filament.components.inbox-message-preview';

    protected int $mailPage = 1;

    protected ?int $messageUid = null;

    protected ?string $rawMessage = null;

    protected ?string $messageError = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->hiddenLabel()
            ->columnSpanFull()
            ->childComponents(fn (): array => [
                MailPreviewEntry::make('raw_message')
                    ->state(fn (): ?string => $this->rawMessage)
                    ->key(fn (): string => 'inbox-email-preview-'.($this->messageUid ?? 'none'))
                    ->hiddenLabel()
                    ->contained(false)
                    ->columnSpanFull()
                    ->visible(fn (): bool => filled($this->rawMessage)),
            ], self::PREVIEW_SCHEMA_KEY);
    }

    public function getMailboxState(): array
    {
        $account = $this->selectedAccount();

        if (! $account) {
            return [
                'messages'      => collect(),
                'pagination'    => $this->emptyPagination(),
                'error'         => null,
                'has_account'   => false,
                'message_uid'   => null,
                'raw_message'   => null,
                'message_error' => null,
            ];
        }

        try {
            $mailbox = $this->fetchMessages($account);
            $previousMessageUid = $this->messageUid;

            $this->messageUid ??= $mailbox['messages']->first()['uid'] ?? null;

            if ($this->messageUid !== $previousMessageUid) {
                $this->clearCachedDefaultChildSchemas();
            }

            $this->loadSelectedMessage($account);

            return [
                ...$mailbox,
                'error'         => null,
                'has_account'   => true,
                'message_uid'   => $this->messageUid,
                'raw_message'   => $this->rawMessage,
                'message_error' => $this->messageError,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'messages'      => collect(),
                'pagination'    => $this->emptyPagination(),
                'error'         => $e->getMessage(),
                'has_account'   => true,
                'message_uid'   => null,
                'raw_message'   => null,
                'message_error' => null,
            ];
        }
    }

    #[ExposedLivewireMethod]
    public function nextPage(int $page): void
    {
        $this->mailPage = max(1, $page + 1);
        $this->clearSelectedMessage();
        $this->clearCachedDefaultChildSchemas();
    }

    #[ExposedLivewireMethod]
    public function previousPage(int $page): void
    {
        $this->mailPage = max(1, $page - 1);
        $this->clearSelectedMessage();
        $this->clearCachedDefaultChildSchemas();
    }

    #[ExposedLivewireMethod]
    public function selectMessage(int $uid, int $page = 1): void
    {
        $this->mailPage = max(1, $page);
        $this->clearSelectedMessage();
        $this->messageUid = $uid;
        $this->clearCachedDefaultChildSchemas();
    }

    protected function selectedAccount(): ?InboxAccount
    {
        $accountId = $this->accountId();

        if (blank($accountId)) {
            return null;
        }

        return InboxAccount::query()
            ->where('is_active', true)
            ->find($accountId);
    }

    protected function accountId(): ?int
    {
        $state = $this->getState();

        return filled($state) && is_scalar($state) ? (int) $state : null;
    }

    protected function fetchMessages(InboxAccount $account): array
    {
        $folder = app(InboxConnector::class)->folder($account);

        $query = $folder->messages()
            ->withHeaders()
            ->withFlags();

        if (in_array('SORT', $folder->mailbox()->capabilities(), true)) {
            $query->sortByDesc(ImapSortKey::Arrival);
        } else {
            $query->newest();
        }

        $paginator = $query->paginate(self::MESSAGE_WINDOW, $this->mailPage);

        $messages = $paginator
            ->items()
            ->map(fn (mixed $message): array => $this->messageSummary($message))
            ->filter(fn (array $summary): bool => filled($summary['uid']))
            ->values();

        return [
            'messages'   => $messages,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->total() ? (($paginator->currentPage() - 1) * $paginator->perPage()) + 1 : null,
                'to'           => $paginator->total() ? min($paginator->currentPage() * $paginator->perPage(), $paginator->total()) : null,
                'has_previous' => $paginator->currentPage() > 1,
                'has_more'     => $paginator->hasMorePages(),
            ],
        ];
    }

    protected function messageSummary(mixed $message): array
    {
        try {
            $from = $message->from();
            $date = $message->date();

            return [
                'uid'     => (int) $message->uid(),
                'from'    => $this->formatAddress($from),
                'subject' => $message->subject() ?: __('No subject'),
                'date'    => $this->formatMailDate($date),
                'seen'    => (bool) $message->isSeen(),
            ];
        } catch (Throwable) {
            return [
                'uid'     => null,
                'from'    => __('Unknown sender'),
                'subject' => __('Unreadable message'),
                'date'    => null,
                'seen'    => true,
            ];
        }
    }

    protected function emptyPagination(): array
    {
        return [
            'current_page' => max(1, $this->mailPage()),
            'last_page'    => 1,
            'total'        => 0,
            'from'         => null,
            'to'           => null,
            'has_previous' => false,
            'has_more'     => false,
        ];
    }

    protected function loadSelectedMessage(InboxAccount $account): void
    {
        if ($this->messageUid === null || filled($this->rawMessage) || filled($this->messageError)) {
            return;
        }

        try {
            $this->rawMessage = $this->fetchMessageEml($account, $this->messageUid);
            $this->messageError = blank($this->rawMessage) ? __('Message not found.') : null;
        } catch (Throwable $e) {
            report($e);

            $this->rawMessage = null;
            $this->messageError = $e->getMessage();
        }
    }

    protected function clearSelectedMessage(): void
    {
        $this->messageUid = null;
        $this->rawMessage = null;
        $this->messageError = null;
    }

    protected function fetchMessageEml(InboxAccount $account, int $uid): ?string
    {
        return Cache::remember(
            $this->messageCacheKey($account, $uid),
            now()->addSeconds(self::MESSAGE_CACHE_SECONDS),
            function () use ($account, $uid): ?string {
                $folder = app(InboxConnector::class)->folder($account);

                // Select the folder before issuing a raw RFC822 fetch.
                $folder->messages();

                $response = $folder
                    ->mailbox()
                    ->connection()
                    ->fetch('RFC822', $uid, identifier: ImapFetchIdentifier::Uid)
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

    protected function messageCacheKey(InboxAccount $account, int $uid): string
    {
        return implode(':', [
            'mailing-system',
            'inbox',
            'eml',
            $account->id,
            (string) $account->updated_at?->timestamp,
            $uid,
        ]);
    }

    protected function formatAddress(mixed $address): string
    {
        if (! $address) {
            return __('Unknown sender');
        }

        $name = $address->name();
        $email = $address->email();

        return filled($name) ? "{$name} <{$email}>" : $email;
    }

    protected function mailPage(): int
    {
        return max(1, $this->mailPage);
    }
}
