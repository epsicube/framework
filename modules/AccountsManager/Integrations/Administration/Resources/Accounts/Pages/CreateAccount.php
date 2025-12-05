<?php

declare(strict_types=1);

namespace EpsicubeModules\AccountsManager\Integrations\Administration\Resources\Accounts\Pages;

use EpsicubeModules\AccountsManager\Integrations\Administration\Resources\Accounts\AccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;
}
