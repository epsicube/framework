<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Schemas\DriverAdministration;

use EpsicubeModules\MailingSystem\Facades\Drivers;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\EmptyState;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Throwable;

class SendGridAdministrationPanel
{
    public static function configure(Schema $schema, array $configuration = []): Schema
    {
        try {
            $state = SendGridWebhookManager::inspect($configuration);
        } catch (Throwable $e) {
            report($e);

            return $schema->components([
                Callout::make(__('Provider inspection failed'))
                    ->danger()
                    ->description($e->getMessage()),
            ]);
        }

        $matchedEvents = $state['matched_events'] ?? [];
        $missingEvents = $state['missing_events'] ?? [];
        $otherWebhooks = $state['other_webhooks'] ?? [];
        $configurationError = $state['configuration_error'] ?? null;
        $statusColor = ($state['status_color'] ?? 'gray') === 'success' ? 'success' : 'warning';
        $expectedUrl = $state['expected_url'] ?? Drivers::getWebhookUrl('sendgrid');
        $components = [
            Callout::make($state['status_label'] ?? __('Unknown'))
                ->{$statusColor}()
                ->description(__('Matching webhooks: :webhooks. Enabled events: :configured. Missing events: :missing. Last sync: :date.', [
                    'webhooks'   => $state['matching_webhooks_count'] ?? 0,
                    'configured' => count($matchedEvents),
                    'missing'    => count($missingEvents),
                    'date'       => $state['checked_at_human'] ?? __('never'),
                ]))
                ->footer([
                    Text::make(__('Webhook URL: :url', ['url' => $expectedUrl]))->color('gray'),
                ]),
        ];

        if (($state['is_public_url'] ?? true) === false) {
            $components[] = Callout::make(__('Expected webhook URL is probably not reachable from SendGrid'))
                ->warning()
                ->description(__('Configure a public HTTPS URL before provisioning provider webhooks. Current URL: :url', [
                    'url' => $expectedUrl,
                ]));
        }

        if (filled($configurationError)) {
            $components[] = EmptyState::make(__('SendGrid API key required'))
                ->description($configurationError)
                ->icon(Heroicon::OutlinedKey);
        } else {
            if (($state['disabled_matching_webhooks_count'] ?? 0) > 0) {
                $components[] = Callout::make(__('A matching SendGrid webhook is disabled'))
                    ->warning()
                    ->description(__('The provider has :count matching webhook(s) for this URL, but at least one is disabled.', [
                        'count' => $state['disabled_matching_webhooks_count'],
                    ]));
            }

            $coverageComponents = [];
            if (! empty($matchedEvents)) {
                $coverageComponents[] = TextEntry::make('matched_events')
                    ->label(__('Enabled events'))
                    ->state($matchedEvents)
                    ->badge()
                    ->color('success');
            } else {
                $coverageComponents[] = Callout::make(__('No enabled SendGrid webhook currently points to the Epsicube webhook URL.'))
                    ->color('gray');
            }

            if (! empty($missingEvents)) {
                $coverageComponents[] = Callout::make(__('Missing events'))
                    ->warning()
                    ->description(__('These recommended events are not enabled on the matching webhook yet.'))
                    ->footer([
                        TextEntry::make('missing_events')
                            ->hiddenLabel()
                            ->state($missingEvents)
                            ->badge()
                            ->color('warning'),
                    ]);
            }

            $otherWebhookComponents = [];
            if (! empty($otherWebhooks)) {
                $otherWebhookComponents[] = TextEntry::make('other_webhooks')
                    ->label(__('Registered webhooks'))
                    ->state(array_map(
                        static fn (array $webhook): string => sprintf('%s [%s]', $webhook['url'], implode(', ', $webhook['events'])),
                        $otherWebhooks
                    ))
                    ->listWithLineBreaks()
                    ->color('gray');
            } else {
                $otherWebhookComponents[] = Text::make(__('No other SendGrid event webhooks were found for this API key.'))->color('gray');
            }

            $components[] = Grid::make(['default' => 1, 'xl' => 2])->schema([
                Section::make(__('Coverage'))
                    ->description(__('Events currently enabled on the expected Epsicube webhook.'))
                    ->compact()
                    ->schema($coverageComponents),
                Section::make(__('Other webhooks'))
                    ->description(__('Existing SendGrid Event Webhooks registered on this API key.'))
                    ->compact()
                    ->schema($otherWebhookComponents),
            ]);
        }

        $components[] = Actions::make([
            Action::make('refreshSendGridWebhookState')
                ->label(__('Refresh'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->outlined()
                ->action(function (): void {
                    Notification::make()
                        ->title(__('Provider state refreshed'))
                        ->success()
                        ->send();
                }),
            Action::make('upsertSendGridWebhook')
                ->label(__('Create or update'))
                ->icon(Heroicon::OutlinedCloudArrowUp)
                ->iconPosition(IconPosition::After)
                ->color('primary')
                ->outlined()
                ->requiresConfirmation()
                ->modalHeading(__('Apply SendGrid webhook configuration'))
                ->modalDescription(__('The selected events will remain enabled on the Epsicube webhook. Unselected events will be disabled, and the webhook will be deleted if nothing remains selected.'))
                ->modalWidth('4xl')
                ->schema([
                    CheckboxList::make('events')
                        ->label(__('Events to enable'))
                        ->options(SendGridWebhookManager::eventOptions())
                        ->descriptions(SendGridWebhookManager::eventDescriptions())
                        ->default($matchedEvents)
                        ->columns(3)
                        ->bulkToggleable(),
                ])
                ->disabled(filled($configurationError))
                ->action(function (array $data) use ($configuration): void {
                    $result = SendGridWebhookManager::provision($configuration, $data['events'] ?? []);
                    $title = match ($result['operation']) {
                        'created' => __('SendGrid webhook created'),
                        'updated' => __('SendGrid webhook updated'),
                        'deleted' => __('SendGrid webhook deleted'),
                        default   => __('SendGrid webhook unchanged'),
                    };

                    Notification::make()
                        ->title($title)
                        ->body(__('Removed duplicate or obsolete webhook(s): :deleted.', [
                            'deleted' => $result['deleted'],
                        ]))
                        ->success()
                        ->send();
                }),
        ])->alignStart();

        return $schema->components($components);
    }
}
