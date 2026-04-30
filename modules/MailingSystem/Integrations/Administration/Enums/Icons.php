<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Enums;

use Filament\Support\Icons\Heroicon;

class Icons
{
    public const Heroicon INBOX = Heroicon::OutlinedInbox;

    public const Heroicon INBOX_ACCOUNT = Heroicon::OutlinedInboxStack;

    public const Heroicon MAILER = Heroicon::OutlinedPaperAirplane;

    public const Heroicon OUTBOX = Heroicon::OutlinedInboxStack;
}
