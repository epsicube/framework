<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Schemas\DriverAdministration;

use EpsicubeModules\MailingSystem\Facades\Drivers;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class SendGridWebhookManager
{
    public const array WEBHOOK_EVENTS = [
        'processed',
        'delivered',
        'deferred',
        'bounce',
        'dropped',
        'open',
        'click',
        'spam_report',
        'unsubscribe',
        'group_unsubscribe',
    ];

    /**
     * @return array<string, string>
     */
    public static function eventOptions(): array
    {
        return [
            'processed'         => __('Processed'),
            'delivered'         => __('Delivered'),
            'deferred'          => __('Deferred'),
            'bounce'            => __('Bounce'),
            'dropped'           => __('Dropped'),
            'open'              => __('Open'),
            'click'             => __('Click'),
            'spam_report'       => __('Spam report'),
            'unsubscribe'       => __('Unsubscribe'),
            'group_unsubscribe' => __('Group unsubscribe'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function eventDescriptions(): array
    {
        return [
            'processed'         => __('Message accepted by SendGrid.'),
            'delivered'         => __('Message delivered to the recipient server.'),
            'deferred'          => __('Delivery temporarily deferred.'),
            'bounce'            => __('Permanent delivery failure.'),
            'dropped'           => __('Message dropped before delivery.'),
            'open'              => __('Recipient opened the message.'),
            'click'             => __('Recipient clicked a tracked link.'),
            'spam_report'       => __('Recipient reported the message as spam.'),
            'unsubscribe'       => __('Recipient unsubscribed globally.'),
            'group_unsubscribe' => __('Recipient unsubscribed from the ASM group.'),
        ];
    }

    public static function inspect(array $configuration = []): array
    {
        $expectedUrl = Drivers::getWebhookUrl('sendgrid');

        if (blank($configuration['api_key'] ?? null)) {
            return [
                'status_label'        => __('Credentials missing'),
                'status_color'        => 'gray',
                'configuration_error' => __('Save the SendGrid API key on this mailer to inspect remote Event Webhooks.'),
                'expected_url'        => $expectedUrl,
                'checked_at_human'    => now()->toDateTimeString(),
                'is_public_url'       => self::isPublicWebhookUrl($expectedUrl),
            ];
        }

        $webhooks = self::listWebhooks($configuration);
        $matchingWebhooks = $webhooks
            ->filter(fn (array $webhook) => self::normalizeWebhookUrl($webhook['url']) === self::normalizeWebhookUrl($expectedUrl))
            ->values();
        $matchedWebhooks = $matchingWebhooks
            ->where('enabled', true)
            ->values();

        $matchedEvents = $matchedWebhooks
            ->flatMap(fn (array $webhook) => $webhook['events'])
            ->unique()
            ->sort()
            ->values()
            ->all();

        $missingEvents = array_values(array_diff(self::WEBHOOK_EVENTS, $matchedEvents));
        $disabledMatchingWebhooksCount = $matchingWebhooks->where('enabled', false)->count();

        return [
            'status_label'                     => empty($missingEvents) && $matchedWebhooks->isNotEmpty() && $disabledMatchingWebhooksCount === 0 ? __('Ready') : __('Action required'),
            'status_color'                     => empty($missingEvents) && $matchedWebhooks->isNotEmpty() && $disabledMatchingWebhooksCount === 0 ? 'success' : 'warning',
            'expected_url'                     => $expectedUrl,
            'checked_at_human'                 => now()->toDateTimeString(),
            'matching_webhooks_count'          => $matchedWebhooks->count(),
            'disabled_matching_webhooks_count' => $disabledMatchingWebhooksCount,
            'matched_events'                   => $matchedEvents,
            'missing_events'                   => $missingEvents,
            'other_webhooks'                   => $webhooks
                ->reject(fn (array $webhook) => self::normalizeWebhookUrl($webhook['url']) === self::normalizeWebhookUrl($expectedUrl))
                ->values()
                ->all(),
            'is_public_url' => self::isPublicWebhookUrl($expectedUrl),
        ];
    }

    /**
     * @param  array<int, string>  $events
     * @return array{operation:string,deleted:int}
     */
    public static function provision(array $configuration = [], array $events = []): array
    {
        $state = self::inspect($configuration);
        $expectedUrl = $state['expected_url'];
        $selectedEvents = collect($events)
            ->filter(fn (mixed $event) => in_array($event, self::WEBHOOK_EVENTS, true))
            ->unique()
            ->values()
            ->all();

        $webhooks = self::listWebhooks($configuration);
        $matchingWebhooks = $webhooks
            ->filter(fn (array $webhook) => self::normalizeWebhookUrl($webhook['url']) === self::normalizeWebhookUrl($expectedUrl))
            ->values();
        $existing = $matchingWebhooks->first();

        $payload = [
            'enabled'       => true,
            'url'           => $expectedUrl,
            'friendly_name' => 'Epsicube Mailing System',
        ];

        foreach (self::WEBHOOK_EVENTS as $event) {
            $payload[$event] = in_array($event, $selectedEvents, true);
        }

        $client = Http::baseUrl('https://api.sendgrid.com/v3')
            ->withToken($configuration['api_key'])
            ->acceptJson()
            ->asJson();

        $deleted = 0;

        if ($selectedEvents === []) {
            foreach ($matchingWebhooks as $webhook) {
                $client->delete('/user/webhooks/event/settings/'.$webhook['id'])->throw();
                $deleted++;
            }

            return ['operation' => $deleted > 0 ? 'deleted' : 'noop', 'deleted' => $deleted];
        }

        if ($existing) {
            $client->patch('/user/webhooks/event/settings/'.$existing['id'], $payload)->throw();

            foreach ($matchingWebhooks->slice(1) as $webhook) {
                $client->delete('/user/webhooks/event/settings/'.$webhook['id'])->throw();
                $deleted++;
            }

            return ['operation' => 'updated', 'deleted' => $deleted];
        }

        $client->post('/user/webhooks/event/settings', $payload)->throw();

        return ['operation' => 'created', 'deleted' => 0];
    }

    /**
     * @return Collection<int, array{id:?string,url:string,enabled:bool,events:array<int,string>,friendly_name:?string}>
     */
    protected static function listWebhooks(array $configuration): Collection
    {
        $response = Http::baseUrl('https://api.sendgrid.com/v3')
            ->withToken($configuration['api_key'])
            ->acceptJson()
            ->get('/user/webhooks/event/settings');

        $response->throw();

        $payload = $response->json();
        $items = data_get($payload, 'result');
        if (! is_array($items)) {
            $items = array_is_list($payload) ? $payload : [$payload];
        }

        return collect($items)
            ->filter(fn (mixed $webhook) => is_array($webhook) && filled($webhook['url'] ?? null))
            ->map(function (array $webhook) {
                $events = collect(self::WEBHOOK_EVENTS)
                    ->filter(fn (string $event) => (bool) ($webhook[$event] ?? false))
                    ->values()
                    ->all();

                return [
                    'id'            => $webhook['id'] ?? null,
                    'url'           => (string) ($webhook['url'] ?? ''),
                    'enabled'       => (bool) ($webhook['enabled'] ?? false),
                    'events'        => $events,
                    'friendly_name' => $webhook['friendly_name'] ?? null,
                ];
            });
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
