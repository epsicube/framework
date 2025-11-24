<?php

declare(strict_types=1);

namespace UniGaleModules\AccountsManager\Integrations\Administration\Resources\Accounts\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use UniGaleModules\AccountsManager\Integrations\Administration\Resources\Accounts\AccountResource;

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
