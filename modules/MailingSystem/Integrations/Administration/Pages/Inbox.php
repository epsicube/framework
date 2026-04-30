<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Pages;

use BackedEnum;
use DirectoryTree\ImapEngine\Enums\ImapSortKey;
use EpsicubeModules\MailingSystem\Integrations\Administration\Enums\ApplicationGroup;
use EpsicubeModules\MailingSystem\Integrations\Administration\Enums\Icons;
use EpsicubeModules\MailingSystem\Models\InboxAccount;
use EpsicubeModules\MailingSystem\Services\InboxConnector;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Throwable;
use UnitEnum;

class Inbox extends Page
{
    protected const int MESSAGE_WINDOW = 15;

    protected string $view = 'epsicube-mail::filament.pages.inbox';

    protected static ?string $slug = '/mails/inbox';

    protected static string|BackedEnum|null $navigationIcon = Icons::INBOX;

    protected static ?int $navigationSort = 105;

    protected static string|null|UnitEnum $navigationGroup = ApplicationGroup::MAILS;

    #[Url(as: 'account')]
    public ?int $accountId = null;

    #[Url(as: 'message')]
    public ?int $messageUid = null;

    #[Url(as: 'page', except: 1)]
    public int $mailPage = 1;

    public ?string $search = null;

    public static function getNavigationLabel(): string
    {
        return __('Inbox');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Inbox');
    }

    public function mount(): void
    {
        if ($this->accountId !== null) {
            return;
        }

        $this->accountId = InboxAccount::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->value('id');
    }

    public function updatedAccountId(): void
    {
        $this->messageUid = null;
        $this->mailPage = 1;
    }

    public function updatedSearch(): void
    {
        $this->search = filled($this->search) ? mb_trim((string) $this->search) : null;
        $this->messageUid = null;
        $this->mailPage = 1;
    }

    public function clearSearch(): void
    {
        $this->search = null;
        $this->messageUid = null;
        $this->mailPage = 1;
    }

    public function nextPage(): void
    {
        $this->messageUid = null;
        $this->mailPage++;
    }

    public function previousPage(): void
    {
        $this->messageUid = null;
        $this->mailPage = max(1, $this->mailPage - 1);
    }

    public function selectMessage(int $uid): void
    {
        $this->messageUid = $uid;
    }

    public function accounts(): EloquentCollection
    {
        return InboxAccount::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);
    }

    public function selectedAccount(): ?InboxAccount
    {
        if ($this->accountId === null) {
            return null;
        }

        return InboxAccount::query()
            ->where('is_active', true)
            ->find($this->accountId);
    }

    /**
     * @return array{messages: Collection, pagination: array{current_page: int, last_page: int, total: int, from: int|null, to: int|null, has_previous: bool, has_more: bool}, error: string|null}
     */
    public function mailboxState(): array
    {
        $account = $this->selectedAccount();

        if (! $account) {
            return [
                'messages'   => collect(),
                'pagination' => $this->emptyPagination(),
                'error'      => null,
            ];
        }

        try {
            return [
                ...$this->fetchMessages($account),
                'error' => null,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'messages'   => collect(),
                'pagination' => $this->emptyPagination(),
                'error'      => $e->getMessage(),
            ];
        }
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

        $pagination = [
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'total'        => $paginator->total(),
            'from'         => $paginator->total() ? (($paginator->currentPage() - 1) * $paginator->perPage()) + 1 : null,
            'to'           => $paginator->total() ? min($paginator->currentPage() * $paginator->perPage(), $paginator->total()) : null,
            'has_previous' => $paginator->currentPage() > 1,
            'has_more'     => $paginator->hasMorePages(),
        ];

        if (blank($this->search)) {
            return [
                'messages'   => $messages,
                'pagination' => $pagination,
            ];
        }

        $needle = mb_strtolower($this->search);

        return [
            'messages' => $messages
                ->filter(function (mixed $message) use ($needle): bool {
                    $subject = mb_strtolower((string) ($message['subject'] ?? ''));
                    $from = mb_strtolower((string) ($message['from'] ?? ''));

                    return str_contains($subject, $needle) || str_contains($from, $needle);
                })
                ->values(),
            'pagination' => $pagination,
        ];
    }

    protected function messageSummary(mixed $message): array
    {
        try {
            $from = $message->from();
            $date = $message->date();

            return [
                'uid'     => $message->uid(),
                'from'    => $this->formatAddress($from),
                'subject' => $message->subject() ?: __('No subject'),
                'date'    => $date?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                'seen'    => $message->isSeen(),
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
            'current_page' => max(1, $this->mailPage),
            'last_page'    => 1,
            'total'        => 0,
            'from'         => null,
            'to'           => null,
            'has_previous' => false,
            'has_more'     => false,
        ];
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
}
