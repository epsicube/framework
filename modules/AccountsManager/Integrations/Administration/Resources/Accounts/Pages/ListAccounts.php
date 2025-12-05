<?php

declare(strict_types=1);

namespace EpsicubeModules\AccountsManager\Integrations\Administration\Resources\Accounts\Pages;

use EpsicubeModules\AccountsManager\Integrations\Administration\Resources\Accounts\AccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

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
