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

class MailjetAdministrationPanel
{
    public static function configure(Schema $schema, array $configuration = []): Schema
    {
        try {
            $state = MailjetWebhookManager::inspect($configuration);
        } catch (Throwable $e) {
            return $schema->components([
                Callout::make(__('Provider inspection failed'))->danger()->description($e->getMessage()),
            ]);
        }

        $matchedEvents = $state['matched_events'] ?? [];
        $missingEvents = $state['missing_events'] ?? [];
        $conflictingEvents = $state['conflicting_events'] ?? [];
        $conflictingCallbacks = $state['conflicting_callbacks'] ?? [];
        $otherCallbacks = $state['other_callbacks'] ?? [];
        $configurationError = $state['configuration_error'] ?? null;
        $eventOptions = MailjetWebhookManager::eventOptions();
        $eventDescriptions = MailjetWebhookManager::eventDescriptions();
        $provisionableEvents = array_values(array_diff(array_keys($eventOptions), $conflictingEvents));
        $statusColor = ($state['status_color'] ?? 'gray') === 'success' ? 'success' : 'warning';
        $expectedUrl = $state['expected_url'] ?? Drivers::getWebhookUrl('mailjet');

        $components = [
            Callout::make($state['status_label'] ?? __('Unknown'))
                ->{$statusColor}()
                ->description(__('Configured events: :configured. Missing events: :missing. Last sync: :date.', [
                    'configured' => count($matchedEvents),
                    'missing'    => count($missingEvents),
                    'date'       => $state['checked_at_human'] ?? __('never'),
                ]))
                ->footer([
                    Text::make(__('Webhook URL: :url', ['url' => $expectedUrl]))->color('gray'),
                ]),
        ];

        if (($state['is_public_url'] ?? true) === false) {
            $components[] = Callout::make(__('Expected webhook URL is probably not reachable from Mailjet'))
                ->warning()
                ->description(__('Configure a public HTTPS URL before creating provider webhooks. Current URL: :url', [
                    'url' => $expectedUrl,
                ]));
        }

        if (filled($configurationError)) {
            $components[] = EmptyState::make(__('Mailjet credentials are required'))
                ->description($configurationError)
                ->icon(Heroicon::OutlinedKey);
        } else {
            $coverageComponents = [];
            if (! empty($matchedEvents)) {
                $coverageComponents[] = TextEntry::make('matched_events')
                    ->label(__('Configured events'))
                    ->state($matchedEvents)
                    ->badge()
                    ->color('success');
            } else {
                $coverageComponents[] = Callout::make(__('No Mailjet callback currently points to the Epsicube webhook URL.'))
                    ->color('gray');
            }

            if (! empty($missingEvents)) {
                $coverageComponents[] = Callout::make(__('Missing events'))
                    ->warning()
                    ->description(__('These events are not yet routed to the Epsicube webhook.'))
                    ->footer([
                        TextEntry::make('missing_events')
                            ->hiddenLabel()
                            ->state($missingEvents)
                            ->badge()
                            ->color('warning'),
                    ]);
            }

            if (! empty($conflictingEvents)) {
                $coverageComponents[] = Callout::make(__('Conflicting events detected'))
                    ->danger()
                    ->description(__('Some Mailjet primary callbacks for these events currently point to another URL. They will only be overwritten if you explicitly allow it in the action modal.'))
                    ->footer([
                        TextEntry::make('conflicting_events')
                            ->hiddenLabel()
                            ->state($conflictingEvents)
                            ->badge()
                            ->color('danger'),
                    ]);
            }

            $otherWebhookComponents = [];
            if (! empty($otherCallbacks)) {
                $otherWebhookComponents[] = TextEntry::make('other_callbacks')
                    ->label(__('Registered callbacks'))
                    ->state(array_map(
                        static fn (array $callback): string => sprintf('%s -> %s', $callback['event'], $callback['url']),
                        $otherCallbacks
                    ))
                    ->listWithLineBreaks()
                    ->color('gray');
            } else {
                $otherWebhookComponents[] = Text::make(__('No other Mailjet callbacks were found for this API key.'))->color('gray');
            }

            $components[] = Grid::make(['default' => 1, 'xl' => 2])->schema([
                Section::make(__('Coverage'))
                    ->description(! empty($conflictingEvents)
                        ? __('Events currently routed to the expected Epsicube webhook. Some events are conflicting and require explicit override.')
                        : __('Events currently routed to the expected Epsicube webhook.'))
                    ->icon(! empty($conflictingEvents) ? Heroicon::OutlinedExclamationTriangle : null)
                    ->iconColor(! empty($conflictingEvents) ? 'danger' : null)
                    ->compact()
                    ->schema($coverageComponents),
                Section::make(__('Other callbacks'))
                    ->description(__('Existing Mailjet callback URLs registered on this API key.'))
                    ->compact()
                    ->schema($otherWebhookComponents),
            ]);
        }

        $components[] = Actions::make([
            Action::make('refreshMailjetWebhookState')
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
            Action::make('provisionMailjetWebhooks')
                ->label(__('Create or update'))
                ->icon(Heroicon::OutlinedCloudArrowUp)
                ->iconPosition(IconPosition::After)
                ->color('primary')
                ->outlined()
                ->requiresConfirmation()
                ->modalHeading(__('Apply Mailjet callback configuration'))
                ->modalDescription(__('The selected events will remain active on Mailjet. Unselected events targeting the Epsicube webhook will be removed.'))
                ->modalWidth('4xl')
                ->schema([
                    CheckboxList::make('events')
                        ->label(__('Events to provision'))
                        ->options(array_intersect_key($eventOptions, array_flip($provisionableEvents)))
                        ->descriptions(array_intersect_key($eventDescriptions, array_flip($provisionableEvents)))
                        ->default(array_values(array_intersect($matchedEvents, $provisionableEvents)))
                        ->columns(3)
                        ->bulkToggleable(),
                    Section::make(__('Risky overrides'))
                        ->compact()
                        ->description(__('This will overwrite existing Mailjet primary callbacks that currently point to other endpoints.'))
                        ->visible(! empty($conflictingCallbacks))
                        ->schema([
                            Callout::make(__('Warning: this may break other integrations.'))
                                ->danger()
                                ->description(__('Only enable overrides for events you explicitly want to reroute to this mailer.')),
                            CheckboxList::make('override_conflicts')
                                ->label(__('Events to override'))
                                ->options(array_intersect_key($eventOptions, array_flip($conflictingEvents)))
                                ->descriptions(array_intersect_key($eventDescriptions, array_flip($conflictingEvents)))
                                ->columns(3)
                                ->bulkToggleable(),
                        ])
                        ->extraAttributes(['class' => 'fi-color-danger']),
                ])
                ->disabled(filled($configurationError))
                ->action(function (array $data) use ($configuration): void {
                    $overrideEvents = $data['override_conflicts'] ?? [];
                    $selectedEvents = array_values(array_unique([
                        ...($data['events'] ?? []),
                        ...$overrideEvents,
                    ]));

                    $result = MailjetWebhookManager::provision(
                        $configuration,
                        $selectedEvents,
                        $overrideEvents !== [],
                    );

                    Notification::make()
                        ->title(__('Mailjet callbacks applied'))
                        ->body(__('Created :created callback(s), updated :updated callback(s), deleted :deleted callback(s), skipped :skipped conflicting callback(s).', [
                            'created' => $result['created'],
                            'updated' => $result['updated'],
                            'deleted' => $result['deleted'],
                            'skipped' => $result['skipped'],
                        ]))
                        ->success()
                        ->send();
                }),
        ])->alignStart();

        return $schema->components($components);
    }
}
