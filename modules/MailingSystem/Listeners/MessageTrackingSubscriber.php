<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Listeners;

use DB;
use EpsicubeModules\MailingSystem\Enums\MessageEngagement;
use EpsicubeModules\MailingSystem\Events\MessageDeliveryEvent;
use EpsicubeModules\MailingSystem\Events\MessageEngagementEvent;
use EpsicubeModules\MailingSystem\Models\Message;
use Illuminate\Contracts\Events\Dispatcher;

class MessageTrackingSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen([MessageDeliveryEvent::class, MessageEngagementEvent::class], $this->handleTracking(...));
    }

    public function handleTracking(MessageDeliveryEvent|MessageEngagementEvent $event): void
    {
        DB::transaction(function () use ($event) {
            $message = Message::query()
                ->whereRelation('outbox', 'id', $event->getOutboxId())
                ->where('recipient', $event->getRecipientEmail())
                ->lockForUpdate()
                ->first();

            if (! $message) {
                return;
            }

            if ($event instanceof MessageDeliveryEvent) {
                $newStatus = $event->getStatus();
                if ($message->status === null || $newStatus->priority() >= $message->status->priority()) {
                    $message->status = $newStatus;
                }
            }

            if ($event instanceof MessageEngagementEvent) {
                $newEngagement = $event->getEngagement();

                if ($newEngagement === MessageEngagement::OPENED) {
                    $message->opened_count++;
                } elseif ($newEngagement === MessageEngagement::CLICKED) {
                    $message->clicked_count++;
                }

                if ($message->engagement === null || $newEngagement->priority() >= $message->engagement->priority()) {
                    $message->engagement = $newEngagement;
                }
            }

            $message->save();
        });

    }
}
