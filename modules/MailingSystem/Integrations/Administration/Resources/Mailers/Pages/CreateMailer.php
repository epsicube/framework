<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Pages;

use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\MailerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMailer extends CreateRecord
{
    protected static string $resource = MailerResource::class;
}
