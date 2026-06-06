<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Console\Commands;

use DirectoryTree\ImapEngine\MessageInterface;
use DirectoryTree\ImapEngine\MessageQueryInterface;
use EpsicubeModules\MailingSystem\Events\MessageReceived;
use EpsicubeModules\MailingSystem\Models\InboxAccount;
use EpsicubeModules\MailingSystem\Services\InboxConnector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Throwable;

class InboxWatchCommand extends Command
{
    protected $signature = '
        inbox:watch 
        {accountId : The ID of the inbox account} 
        {--timeout=30 : IMAP timeout in seconds} 
        {--attempts=5 : Max reconnection attempts} 
    ';

    protected $description = 'Watch an inbox account for new messages using IDLE or polling.';

    private int $attempts = 0;

    private ?InboxAccount $account = null;

    public function handle(InboxConnector $connector): int
    {
        $this->account = InboxAccount::findOrFail($this->argument('accountId'));
        $timeout = (int) $this->option('timeout');

        $folder = $connector->folder($this->account);

        $this->info("Watching account [{$this->account->name}]...");

        while (true) {
            try {
                match (in_array('IDLE', $connector->mailbox($this->account)->capabilities(), true)) {
                    true  => $folder->idle($this->onMessage(...), $this->onQuery(...), $timeout),
                    false => $folder->poll($this->onMessage(...), $this->onQuery(...), $timeout),
                };
            } catch (Throwable $e) {
                if ($this->shouldStop($e)) {
                    $this->warn('Stopping: '.$e->getMessage());

                    return self::SUCCESS;
                }

                if ($this->isDisconnection($e)) {
                    $this->warn('Disconnection detected. Retrying in 2s...');
                    sleep(2);

                    continue;
                }

                if ($this->attempts >= (int) $this->option('attempts')) {
                    $this->error("Max attempts reached. Critical failure: {$e->getMessage()}");
                    throw $e;
                }

                $this->attempts++;
            }
        }
    }

    private function onMessage(MessageInterface $message): void
    {
        $this->attempts = 0;
        $this->info("Received message [{$message->uid()}] from [{$this->account->name}] at ".Date::now()->toDateTimeString());
        // TODO use only one message library inside this module
        Event::dispatch(new MessageReceived($message, $this->account));
    }

    private function onQuery(MessageQueryInterface $query): MessageQueryInterface
    {
        return $query->withFlags()->withBody()->withHeaders();
    }

    protected function shouldStop(Throwable $e): bool
    {
        return Str::contains($e->getMessage(), ['no longer exist'], true);
    }

    protected function isDisconnection(Throwable $e): bool
    {
        return Str::contains($e->getMessage(), [
            'connection reset by peer', 'temporary system problem', 'failed to fetch content',
            'connection failed', 'empty response', 'not connected', 'no response',
            'broken pipe', 'unavailable',
        ], true);
    }
}
