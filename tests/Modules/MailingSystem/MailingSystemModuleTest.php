<?php

declare(strict_types=1);

use Epsicube\Tests\Modules\MailingSystem\Fixtures\EmptyWebhookDriver;
use Epsicube\Tests\Modules\MailingSystem\Fixtures\TestWebhookDriver;
use EpsicubeModules\MailingSystem\Enums\MessageEngagement;
use EpsicubeModules\MailingSystem\Enums\MessageStatus;
use EpsicubeModules\MailingSystem\Events\MessageDeliveryEvent;
use EpsicubeModules\MailingSystem\Events\MessageEngagementEvent;
use EpsicubeModules\MailingSystem\Facades\Drivers;
use EpsicubeModules\MailingSystem\MailingSystemModule;
use EpsicubeModules\MailingSystem\Mails\Drivers\Mailjet\MailjetServiceProvider;
use EpsicubeModules\MailingSystem\Models\Mailer as MailerModel;
use EpsicubeModules\MailingSystem\Models\Message;
use EpsicubeModules\MailingSystem\Models\Outbox;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;

test('mailing system module declares expected metadata and integration hooks', function () {
    $this->configureModules([
        MailingSystemModule::class,
    ]);

    $module = $this->module('core::mailing-system');

    expect($module->identifier)->toBe('core::mailing-system')
        ->and($module->identity->name)->toBe('Mailing System')
        ->and($module->providers)->toBe([
            MailingSystemModule::class,
            MailjetServiceProvider::class,
        ])
        ->and($module->supports->supports)->toHaveCount(2)
        ->and($module->options->properties())->toBe([]);
});

test('mailing system registers its built-in drivers when enabled', function () {
    $this->configureModules([
        MailingSystemModule::class,
    ], [
        'core::mailing-system' => true,
    ]);

    expect(Drivers::all())->toHaveKeys(['laravel', 'mailjet', 'sendgrid']);
});

test('webhook endpoint returns no content for unknown drivers', function () {
    $this->configureModules([
        MailingSystemModule::class,
    ], [
        'core::mailing-system' => true,
    ]);

    $this->postJson('/mailing-system/_webhook/unknown-driver')
        ->assertNoContent();
});

test('webhook endpoint dispatches parsed events for webhook drivers', function () {
    $this->configureModules([
        MailingSystemModule::class,
    ], [
        'core::mailing-system' => true,
    ]);
    Drivers::register(new TestWebhookDriver);

    Event::fake([MessageDeliveryEvent::class]);

    $this->postJson('/mailing-system/_webhook/tests-webhook', ['event' => 'delivered'])
        ->assertOk()
        ->assertJson(['status' => 'accepted']);

    Event::assertDispatched(MessageDeliveryEvent::class, function (MessageDeliveryEvent $event): bool {
        return $event->getOutboxId() === 'outbox-1'
            && $event->getRecipientEmail() === 'john@example.test';
    });
});

test('webhook endpoint reports unprocessable payloads when a driver returns no events', function () {
    $this->configureModules([
        MailingSystemModule::class,
    ], [
        'core::mailing-system' => true,
    ]);
    Drivers::register(new EmptyWebhookDriver);

    $this->postJson('/mailing-system/_webhook/tests-empty-webhook', ['event' => 'ignored'])
        ->assertOk()
        ->assertJson(['status' => 'unprocessable']);
});

test('mailer model resolves to a sendable laravel mailer using stored configuration', function () {
    $this->configureModules([
        MailingSystemModule::class,
    ], [
        'core::mailing-system' => true,
    ]);
    migrateMailingSystemTables();

    $mailerModel = MailerModel::query()->create([
        'name'          => 'Transactional',
        'driver'        => 'laravel',
        'configuration' => ['name' => 'array'],
        'from_email'    => 'noreply@example.test',
        'from_name'     => 'Notifications',
    ]);

    $mailer = $mailerModel->toMailer();

    expect($mailer->getSymfonyTransport()->__toString())->toContain('array');
});

test('message tracking subscriber applies delivery and engagement priorities to persisted messages', function () {
    $this->configureModules([
        MailingSystemModule::class,
    ], [
        'core::mailing-system' => true,
    ]);
    migrateMailingSystemTables();

    $mailer = MailerModel::query()->create([
        'name'          => 'Transactional',
        'driver'        => 'laravel',
        'configuration' => ['name' => 'array'],
        'from_email'    => 'noreply@example.test',
    ]);

    $outbox = Outbox::query()->create([
        'mailer_id' => $mailer->id,
        'subject'   => 'Welcome',
    ]);

    $message = Message::query()->create([
        'outbox_id' => $outbox->id,
        'recipient' => 'john@example.test',
        'type'      => 'to',
    ]);

    event(new MessageDeliveryEvent(
        outboxId: (string) $outbox->id,
        recipientEmail: 'john@example.test',
        status: MessageStatus::RECEIVED,
        time: Carbon::parse('2026-05-02 10:00:00'),
    ));

    event(new MessageDeliveryEvent(
        outboxId: (string) $outbox->id,
        recipientEmail: 'john@example.test',
        status: MessageStatus::DELIVERED,
        time: Carbon::parse('2026-05-02 10:05:00'),
    ));

    event(new MessageDeliveryEvent(
        outboxId: (string) $outbox->id,
        recipientEmail: 'john@example.test',
        status: MessageStatus::RECEIVED,
        time: Carbon::parse('2026-05-02 10:10:00'),
    ));

    event(new MessageEngagementEvent(
        outboxId: (string) $outbox->id,
        recipientEmail: 'john@example.test',
        engagement: MessageEngagement::OPENED,
        time: Carbon::parse('2026-05-02 10:15:00'),
    ));

    event(new MessageEngagementEvent(
        outboxId: (string) $outbox->id,
        recipientEmail: 'john@example.test',
        engagement: MessageEngagement::CLICKED,
        time: Carbon::parse('2026-05-02 10:16:00'),
    ));

    event(new MessageEngagementEvent(
        outboxId: (string) $outbox->id,
        recipientEmail: 'john@example.test',
        engagement: MessageEngagement::OPENED,
        time: Carbon::parse('2026-05-02 10:17:00'),
    ));

    $message->refresh();

    expect($message->status)->toBe(MessageStatus::DELIVERED)
        ->and($message->engagement)->toBe(MessageEngagement::CLICKED)
        ->and($message->opened_count)->toBe(2)
        ->and($message->clicked_count)->toBe(1);
});

function migrateMailingSystemTables(): void
{
    Artisan::call('migrate', ['--force' => true]);
}
