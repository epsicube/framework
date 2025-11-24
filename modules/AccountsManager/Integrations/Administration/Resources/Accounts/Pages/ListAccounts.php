<?php

declare(strict_types=1);

namespace UniGaleModules\AccountsManager\Integrations\Administration\Resources\Accounts\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use UniGaleModules\AccountsManager\Integrations\Administration\Resources\Accounts\AccountResource;

class ListAccounts extends ListRecords
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
