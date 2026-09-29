<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Pages;

use EpsicubeModules\MailingSystem\Facades\Drivers;
use EpsicubeModules\MailingSystem\Integrations\Administration\Contracts\HasMailerAdministrationPanel;
use EpsicubeModules\MailingSystem\Integrations\Administration\Enums\Icons;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\MailerResource;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\RelationManagers\OutboxesRelationManager;
use EpsicubeModules\MailingSystem\Mails\EpsicubeMail;
use EpsicubeModules\MailingSystem\Models\Mailer;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TagsInput;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Throwable;

class ViewMailer extends ViewRecord
{
    protected static string $resource = MailerResource::class;

    public function getRelationManagers(): array
    {
        return [
            'outboxes' => OutboxesRelationManager::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('provider_infolist')
                ->label(__('Advanced'))
                ->outlined()->color('gray')->icon(Icons::SETTINGS)
                ->visible(fn (Mailer $record) => Drivers::safeGet($record->driver) instanceof HasMailerAdministrationPanel)
                ->modalFooterActions([])
                ->schema(function (Schema $schema, Mailer $record) {
                    $driverInstance = Drivers::safeGet($record->driver);
                    if (! ($driverInstance instanceof HasMailerAdministrationPanel)) {
                        return [];
                    }

                    return $driverInstance::configureDriverPanel($schema, $record->configuration ?? []);
                }),

            Action::make('send_test_mail')
                ->label(__('Send test mail'))
                ->outlined()->color('gray')->icon(Icons::MAILER)
                ->modalHeading(__('Send a test mail'))
                ->modalSubmitActionLabel(__('Send'))
                ->schema([
                    TagsInput::make('to')
                        ->label(__('To'))
                        ->placeholder(__('Add an email address'))
                        ->nestedRecursiveRules(['email'])
                        ->required(),
                ])
                ->successNotificationTitle(__('Test mail sent'))
                ->failureNotificationTitle(__('Failed to send test mail'))
                ->action(function (array $data, Mailer $record, Action $action): void {
                    try {
                        $mail = (new EpsicubeMail)
                            ->setTemplate('_blank')
                            ->subject(__('Test mail'))
                            ->to($data['to'])
                            ->with(['content' => __('Test email')]);

                        $mail->send($record->toMailer());
                    } catch (Throwable $e) {
                        $action->failureNotificationBody($e->getMessage());
                        $action->failure();

                        return;
                    }

                    $action->success();
                }),

            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
