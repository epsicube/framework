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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

class InboxMessagePreview extends Entry
{
    use FormatsMailDates;

    protected const int MESSAGE_CACHE_SECONDS = 300;

    protected const int MESSAGE_WINDOW = 15;

    protected const string PREVIEW_SCHEMA_KEY = 'preview';

    protected string $view = 'epsicube-mail::filament.components.inbox-message-preview';

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->hiddenLabel()
            ->columnSpanFull()
            ->childComponents(fn (): array => [
                MailPreviewEntry::make('raw_message')
                    ->state(fn (): ?string => $this->getStoredRawMessage())
                    ->key('inbox-email-preview-'.$this->getStoredMessageUid())
                    ->hiddenLabel()
                    ->contained(false)
                    ->columnSpanFull()
                    ->visible(fn (): bool => filled($this->getStoredRawMessage())),
            ], self::PREVIEW_SCHEMA_KEY);
    }

    public function getMailboxState(): array
    {
        $this->resetStoredStateWhenAccountChanges();

        $state = $this->storedState();

        if (! ($state['is_loaded'] ?? false)) {
            return [
                'messages'      => collect(),
                'pagination'    => $this->emptyPagination(),
                'error'         => null,
                'has_account'   => true,
                'is_loaded'     => false,
                'search'        => $state['search'] ?? null,
                'message_uid'   => $state['message_uid'] ?? null,
                'raw_message'   => $state['raw_message'] ?? null,
                'message_error' => $state['message_error'] ?? null,
            ];
        }

        $account = $this->selectedAccount();

        if (! $account) {
            return [
                'messages'      => collect(),
                'pagination'    => $this->emptyPagination(),
                'error'         => null,
                'has_account'   => InboxAccount::query()->where('is_active', true)->exists(),
                'is_loaded'     => true,
                'search'        => $state['search'] ?? null,
                'message_uid'   => $state['message_uid'] ?? null,
                'raw_message'   => $state['raw_message'] ?? null,
                'message_error' => $state['message_error'] ?? null,
            ];
        }

        try {
            $mailbox = $this->fetchMessages($account);

            $this->selectFirstMessageWhenNeeded($mailbox['messages']);
            $this->loadSelectedMessageWhenNeeded();

            return [
                ...$mailbox,
                'error'         => null,
                'has_account'   => true,
                'is_loaded'     => true,
                'search'        => $this->storedValue('search'),
                'message_uid'   => $this->getStoredMessageUid(),
                'raw_message'   => $this->getStoredRawMessage(),
                'message_error' => $this->storedValue('message_error'),
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'messages'      => collect(),
                'pagination'    => $this->emptyPagination(),
                'error'         => $e->getMessage(),
                'has_account'   => true,
                'is_loaded'     => true,
                'search'        => $state['search'] ?? null,
                'message_uid'   => $state['message_uid'] ?? null,
                'raw_message'   => $state['raw_message'] ?? null,
                'message_error' => $state['message_error'] ?? null,
            ];
        }
    }

    #[ExposedLivewireMethod]
    public function loadMailbox(): void
    {
        $this->mergeStoredState(['is_loaded' => true]);
    }

    #[ExposedLivewireMethod]
    public function applySearch(?string $search = null): void
    {
        $this->mergeStoredState([
            'is_loaded'     => true,
            'search'        => filled($search) ? mb_trim($search) : null,
            'message_uid'   => null,
            'raw_message'   => null,
            'message_error' => null,
            'mail_page'     => 1,
        ]);
    }

    #[ExposedLivewireMethod]
    public function resetSearch(): void
    {
        $this->applySearch();
    }

    #[ExposedLivewireMethod]
    public function nextPage(): void
    {
        $this->mergeStoredState([
            'message_uid'   => null,
            'raw_message'   => null,
            'message_error' => null,
            'mail_page'     => $this->mailPage() + 1,
        ]);
    }

    #[ExposedLivewireMethod]
    public function previousPage(): void
    {
        $this->mergeStoredState([
            'message_uid'   => null,
            'raw_message'   => null,
            'message_error' => null,
            'mail_page'     => max(1, $this->mailPage() - 1),
        ]);
    }

    #[ExposedLivewireMethod]
    public function selectMessage(int $uid): void
    {
        if ($this->getStoredMessageUid() === $uid && filled($this->getStoredRawMessage())) {
            return;
        }

        $this->mergeStoredState([
            'message_uid'   => $uid,
            'raw_message'   => null,
            'message_error' => null,
        ]);

        $this->loadSelectedMessageWhenNeeded();
    }

    protected function selectedAccount(): ?InboxAccount
    {
        $accountId = $this->getState();

        if (blank($accountId)) {
            return null;
        }

        return InboxAccount::query()
            ->where('is_active', true)
            ->find((int) $accountId);
    }

    protected function resetStoredStateWhenAccountChanges(): void
    {
        $accountId = $this->getState();
        $storedAccountId = $this->storedValue('account_id');

        if ((string) $storedAccountId === (string) $accountId) {
            return;
        }

        $this->mergeStoredState([
            'account_id'    => $accountId,
            'is_loaded'     => false,
            'search'        => null,
            'message_uid'   => null,
            'raw_message'   => null,
            'message_error' => null,
            'mail_page'     => 1,
        ]);
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

        $paginator = $query->paginate(self::MESSAGE_WINDOW, $this->mailPage());

        $messages = $paginator
            ->items()
            ->map(fn (mixed $message): array => $this->messageSummary($message))
            ->filter(fn (array $summary): bool => filled($summary['uid']))
            ->values();

        if (filled($this->storedValue('search'))) {
            $needle = mb_strtolower((string) $this->storedValue('search'));

            $messages = $messages
                ->filter(function (array $message) use ($needle): bool {
                    $subject = mb_strtolower((string) ($message['subject'] ?? ''));
                    $from = mb_strtolower((string) ($message['from'] ?? ''));

                    return str_contains($subject, $needle) || str_contains($from, $needle);
                })
                ->values();
        }

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

    protected function selectFirstMessageWhenNeeded(Collection $messages): void
    {
        if (filled($this->getStoredMessageUid())) {
            return;
        }

        $firstUid = $messages->first()['uid'] ?? null;

        if (filled($firstUid)) {
            $this->mergeStoredState(['message_uid' => (int) $firstUid]);
        }
    }

    protected function loadSelectedMessageWhenNeeded(): void
    {
        $account = $this->selectedAccount();
        $messageUid = $this->getStoredMessageUid();

        if (! $account || $messageUid === null || filled($this->getStoredRawMessage()) || filled($this->storedValue('message_error'))) {
            return;
        }

        try {
            $rawMessage = $this->fetchMessageEml($account, $messageUid);

            $this->mergeStoredState([
                'raw_message'   => $rawMessage,
                'message_error' => blank($rawMessage) ? __('Message not found.') : null,
            ]);
        } catch (Throwable $e) {
            report($e);

            $this->mergeStoredState([
                'raw_message'   => null,
                'message_error' => $e->getMessage(),
            ]);
        }
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

    protected function getStoredMessageUid(): ?int
    {
        $messageUid = $this->storedValue('message_uid');

        return filled($messageUid) ? (int) $messageUid : null;
    }

    protected function getStoredRawMessage(): ?string
    {
        $rawMessage = $this->storedValue('raw_message');

        return is_string($rawMessage) && $rawMessage !== '' ? $rawMessage : null;
    }

    protected function mailPage(): int
    {
        return max(1, (int) ($this->storedValue('mail_page') ?? 1));
    }

    protected function storedState(): array
    {
        return data_get($this->getLivewire(), $this->storePath(), []);
    }

    protected function storedValue(string $key): mixed
    {
        return data_get($this->getLivewire(), $this->storePath().'.'.$key);
    }

    protected function mergeStoredState(array $state): void
    {
        $livewire = $this->getLivewire();

        data_set($livewire, $this->storePath(), [
            ...$this->storedState(),
            ...$state,
        ]);
    }

    protected function storePath(): string
    {
        return 'inboxMessagePreviewState.'.$this->getKey();
    }
}
