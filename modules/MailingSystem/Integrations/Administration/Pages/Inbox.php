<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Pages;

use BackedEnum;
use EpsicubeModules\MailingSystem\Integrations\Administration\Enums\ApplicationGroup;
use EpsicubeModules\MailingSystem\Integrations\Administration\Enums\Icons;
use EpsicubeModules\MailingSystem\Integrations\Administration\Filament\Components\InboxMessagePreview;
use EpsicubeModules\MailingSystem\Models\InboxAccount;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Attributes\Url;
use UnitEnum;

class Inbox extends Page
{
    protected string $view = 'epsicube-mail::filament.pages.inbox';

    protected static ?string $slug = '/mails/inbox';

    protected static string|BackedEnum|null $navigationIcon = Icons::INBOX;

    protected static ?int $navigationSort = 105;

    protected static string|null|UnitEnum $navigationGroup = ApplicationGroup::MAILS;

    #[Url(as: 'account')]
    public ?int $accountId = null;

    public array $inboxMessagePreviewState = [];

    public static function getNavigationLabel(): string
    {
        return __('Inbox');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Inbox');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('accountId')
                ->label(__('Account'))
                ->options(fn (): array => $this->accounts()->pluck('name', 'id')->all())
                ->live()
                ->native(),

            InboxMessagePreview::make('inbox')
                ->state(fn (): ?int => $this->accountId)
                ->columnSpanFull(),
        ]);
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

    public function accounts(): EloquentCollection
    {
        return InboxAccount::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);
    }
}
