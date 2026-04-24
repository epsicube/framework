<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Schemas\DriverAdministration;

use EpsicubeModules\MailingSystem\Facades\Drivers;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class MailjetWebhookManager
{
    /**
     * @return array<string, string>
     */
    public static function eventOptions(): array
    {
        return [
            'sent'    => __('Sent'),
            'open'    => __('Open'),
            'click'   => __('Click'),
            'bounce'  => __('Bounce'),
            'blocked' => __('Blocked'),
            'spam'    => __('Spam'),
            'unsub'   => __('Unsubscribe'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function eventDescriptions(): array
    {
        return [
            'sent'    => __('Delivery accepted by Mailjet.'),
            'open'    => __('Recipient opened the message.'),
            'click'   => __('Recipient clicked a tracked link.'),
            'bounce'  => __('Permanent delivery failure.'),
            'blocked' => __('Message was blocked or dropped.'),
            'spam'    => __('Recipient reported the message as spam.'),
            'unsub'   => __('Recipient unsubscribed.'),
        ];
    }

    public static function inspect(array $configuration = []): array
    {
        $expectedUrl = Drivers::getWebhookUrl('mailjet');

        if (blank($configuration['public_key'] ?? null) || blank($configuration['private_key'] ?? null)) {
            return [
                'status_label'        => __('Credentials missing'),
                'status_color'        => 'gray',
                'configuration_error' => __('Save both Mailjet public and private keys on this mailer to inspect remote callbacks.'),
                'expected_url'        => $expectedUrl,
                'checked_at_human'    => now()->toDateTimeString(),
                'is_public_url'       => self::isPublicWebhookUrl($expectedUrl),
            ];
        }

        $callbacks = self::listCallbacks($configuration);

        $matchedCallbacks = $callbacks
            ->where('is_backup', false)
            ->filter(fn (array $callback) => self::normalizeWebhookUrl($callback['url']) === self::normalizeWebhookUrl($expectedUrl))
            ->values();
        $conflictingCallbacks = $callbacks
            ->where('is_backup', false)
            ->filter(fn (array $callback) => self::normalizeWebhookUrl($callback['url']) !== self::normalizeWebhookUrl($expectedUrl))
            ->values();

        $allEvents = array_keys(self::eventOptions());
        $matchedEvents = $matchedCallbacks->pluck('event')->filter()->unique()->sort()->values()->all();
        $conflictingEvents = $conflictingCallbacks->pluck('event')->filter()->unique()->sort()->values()->all();
        $missingEvents = array_values(array_diff($allEvents, $matchedEvents, $conflictingEvents));

        return [
            'status_label'          => empty($missingEvents) ? __('Ready') : __('Action required'),
            'status_color'          => empty($missingEvents) ? 'success' : 'warning',
            'expected_url'          => $expectedUrl,
            'checked_at_human'      => now()->toDateTimeString(),
            'matched_events'        => $matchedEvents,
            'missing_events'        => $missingEvents,
            'conflicting_events'    => $conflictingEvents,
            'conflicting_callbacks' => $conflictingCallbacks->values()->all(),
            'other_callbacks'       => $callbacks
                ->reject(fn (array $callback) => self::normalizeWebhookUrl($callback['url']) === self::normalizeWebhookUrl($expectedUrl))
                ->values()
                ->all(),
            'is_public_url' => self::isPublicWebhookUrl($expectedUrl),
        ];
    }

    /**
     * @param  array<int, string>  $events
     * @return array{created:int,updated:int,deleted:int,skipped:int}
     */
    public static function provision(array $configuration = [], array $events = [], bool $overwriteConflicts = false): array
    {
        $state = self::inspect($configuration);
        $callbacks = self::listCallbacks($configuration);
        $allowedEvents = array_keys(self::eventOptions());
        $selectedEvents = collect($events)
            ->filter(fn (mixed $event) => in_array($event, $allowedEvents, true))
            ->unique()
            ->values()
            ->all();

        $client = Http::baseUrl('https://api.mailjet.com/v3/REST')
            ->withBasicAuth($configuration['public_key'], $configuration['private_key'])
            ->asJson();

        $created = 0;
        $updated = 0;
        $deleted = 0;
        $skipped = 0;

        $matchingCallbacks = $callbacks
            ->where('is_backup', false)
            ->filter(fn (array $callback) => self::normalizeWebhookUrl($callback['url']) === self::normalizeWebhookUrl($state['expected_url']))
            ->values();

        foreach ($matchingCallbacks as $callback) {
            if (in_array($callback['event'], $selectedEvents, true)) {
                continue;
            }

            $client->delete('/eventcallbackurl/'.$callback['id'])->throw();
            $deleted++;
        }

        foreach ($selectedEvents as $event) {
            $existing = $matchingCallbacks
                ->first(fn (array $callback) => $callback['event'] === $event);

            $foreignPrimary = $callbacks->first(fn (array $callback) => $callback['event'] === $event
                && $callback['is_backup'] === false
                && self::normalizeWebhookUrl($callback['url']) !== self::normalizeWebhookUrl($state['expected_url']));

            $payload = [
                'EventType' => $event,
                'Url'       => $state['expected_url'],
                'Version'   => 2,
                'IsBackup'  => false,
            ];

            if ($existing) {
                $client->put('/eventcallbackurl/'.$existing['id'], [
                    'ID' => $existing['id'],
                    ...$payload,
                ])->throw();

                $updated++;

                continue;
            }

            if ($foreignPrimary) {
                if ($overwriteConflicts) {
                    $client->put('/eventcallbackurl/'.$foreignPrimary['id'], [
                        'ID' => $foreignPrimary['id'],
                        ...$payload,
                    ])->throw();

                    $updated++;

                    continue;
                }

                $skipped++;

                continue;
            }

            $client->post('/eventcallbackurl', $payload)->throw();
            $created++;
        }

        return ['created' => $created, 'updated' => $updated, 'deleted' => $deleted, 'skipped' => $skipped];
    }

    /**
     * @return Collection<int, array{id:mixed,event:string,url:string,is_backup:bool,status:?string,version:mixed}>
     */
    protected static function listCallbacks(array $configuration): Collection
    {
        $response = Http::baseUrl('https://api.mailjet.com/v3/REST')
            ->withBasicAuth($configuration['public_key'], $configuration['private_key'])
            ->acceptJson()
            ->get('/eventcallbackurl');

        $response->throw();

        return collect($response->json('Data', []))
            ->map(fn (array $callback) => [
                'id'        => $callback['ID'] ?? null,
                'event'     => self::normalizeEventType($callback['EventType'] ?? null),
                'url'       => (string) ($callback['Url'] ?? ''),
                'is_backup' => (bool) ($callback['IsBackup'] ?? false),
                'status'    => $callback['Status'] ?? null,
                'version'   => $callback['Version'] ?? null,
            ]);
    }

    protected static function normalizeEventType(mixed $eventType): string
    {
        if (is_string($eventType)) {
            return mb_strtolower($eventType);
        }

        return match ((int) $eventType) {
            1       => 'click',
            2       => 'bounce',
            3       => 'spam',
            4       => 'blocked',
            5       => 'sent',
            6       => 'open',
            7       => 'unsub',
            default => (string) $eventType,
        };
    }

    protected static function normalizeWebhookUrl(string $url): string
    {
        return mb_rtrim(mb_strtolower($url), '/');
    }

    protected static function isPublicWebhookUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || blank($host)) {
            return false;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.test')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false && filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return false;
        }

        return true;
    }
}
