<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Events;

use DirectoryTree\ImapEngine\MessageInterface;
use EpsicubeModules\MailingSystem\Models\InboxAccount;

class MessageReceived
{
    public function __construct(public MessageInterface $message, public InboxAccount $account) {}
}
