<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Enums;

use Filament\Support\Icons\Heroicon;

class Icons
{
    public const Heroicon INBOX = Heroicon::OutlinedInboxArrowDown;

    public const Heroicon INBOX_ACCOUNT = Heroicon::OutlinedAtSymbol;

    public const Heroicon MAILER = Heroicon::OutlinedEnvelope;

    public const Heroicon SETTINGS = Heroicon::OutlinedCog6Tooth;

    public const Heroicon OUTBOX = Heroicon::OutlinedPaperAirplane;
}
