<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem;

use Epsicube\Schemas\Properties\BooleanProperty;
use Epsicube\Support\Facades\Options;

class MailingSystemOptions
{
    public static function definition(): array
    {
        return [
            'inject-internal-mailers' => BooleanProperty::make()
                ->title('Enable internal mailers')
                ->description('When enabled, all Laravel mailers will be registered and made available. This is not recommended.')
                ->optional()
                ->default(false),
        ];
    }

    public static function withInternalMailers(): bool
    {
        return Options::get('core::mailing-system', 'inject-internal-mailers');
    }
}
