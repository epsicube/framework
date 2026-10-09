<?php

declare(strict_types=1);

namespace EpsicubeModules\AccountsManager;

use Epsicube\Schemas\Properties\BooleanProperty;
use Epsicube\Schemas\Schema;
use Epsicube\Support\Facades\Options;

class AccountsManagerOptions
{
    public static function configure(Schema $options): void
    {
        $options->append([
            'allow_registration'   => BooleanProperty::make()->title('Allow registration')->optional()->default(true),
            'allow_reset_password' => BooleanProperty::make()->title('Allow reset password')->optional()->default(true),
        ]);
    }

    public static function isRegistrationAllowed(): bool
    {
        return Options::get('core::accounts-manager', 'allow_registration');
    }

    public static function canResetPassword(): bool
    {
        return Options::get('core::accounts-manager', 'allow_reset_password');
    }
}
