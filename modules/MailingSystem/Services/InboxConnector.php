<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Services;

use DirectoryTree\ImapEngine\FolderInterface;
use DirectoryTree\ImapEngine\MailboxInterface;
use EpsicubeModules\MailingSystem\Models\InboxAccount;

class InboxConnector
{
    public function mailbox(InboxAccount $account): MailboxInterface
    {
        return $account->toMailbox();
    }

    public function folder(InboxAccount $account): FolderInterface
    {
        $mailbox = $this->mailbox($account);

        if ($account->folder === 'INBOX') {
            return $mailbox->inbox();
        }

        return $mailbox->folders()->findOrFail($account->folder);
    }

    protected function mailboxName(InboxAccount $account): string
    {
        return "mailing-system-inbox-{$account->id}";
    }
}
