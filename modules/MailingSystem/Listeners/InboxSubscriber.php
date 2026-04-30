<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Listeners;

use DirectoryTree\ImapEngine\Laravel\Events\MessageReceived;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class InboxSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen([MessageReceived::class], $this->handleMessage(...));
    }

    public function handleMessage(MessageReceived $event): void
    {
        $stream = $event->message->parse()->getResourceHandle();
        $eml = stream_get_contents($stream);
        fclose($stream);

        // TODO custom event with internal type
        // TODO trigger dynamic workflows

        Log::info('Message received', [
            $event->message->subject(),
            $event->message->from(),
            $event->message->to(),
            $event->message->cc(),
            $event->message->bcc(),
            $event->message->date(),
            $event->message->uid(),
            $eml,
        ]);

    }
}
