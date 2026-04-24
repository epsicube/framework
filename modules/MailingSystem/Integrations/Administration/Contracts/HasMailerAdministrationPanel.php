<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Contracts;

use Filament\Schemas\Schema;

interface HasMailerAdministrationPanel
{
    public static function configureDriverPanel(Schema $schema, array $configuration = []): Schema;
}
