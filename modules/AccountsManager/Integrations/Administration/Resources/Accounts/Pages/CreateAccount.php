<?php

declare(strict_types=1);

namespace UniGaleModules\AccountsManager\Integrations\Administration\Resources\Accounts\Pages;

use Filament\Resources\Pages\CreateRecord;
use UniGaleModules\AccountsManager\Integrations\Administration\Resources\Accounts\AccountResource;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;
}
