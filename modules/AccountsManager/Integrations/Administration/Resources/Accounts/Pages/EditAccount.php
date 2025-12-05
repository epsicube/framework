<?php

declare(strict_types=1);

namespace EpsicubeModules\AccountsManager\Integrations\Administration\Resources\Accounts\Pages;

use EpsicubeModules\AccountsManager\Integrations\Administration\Resources\Accounts\AccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
