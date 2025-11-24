<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Integrations\Administration\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Throwable;
use UniGaleModules\ExecutionPlatform\Enum\ExecutionStatus;
use UniGaleModules\ExecutionPlatform\Enum\Icons;
use UniGaleModules\ExecutionPlatform\Models\Execution;

class CancelExecutionAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->label(__('Cancel'));
        $this->icon(Icons::CANCEL);

        $this->color('warning');
        $this->requiresConfirmation();
        $this->schema([
            TextInput::make('reason')->label(__('Reason')),
        ]);

        $this->hidden(fn (Execution $record) => $record->status !== ExecutionStatus::PROCESSING);

        $this->action(fn (array $data, Execution $record) => $this->execute($data, $record));
    }

    protected function execute(array $data, Execution $record): void
    {
        try {
            $record->cancel($data['reason']);
            $this->success();
            $this->successNotificationTitle(__('Execution canceled successfully'));
        } catch (Throwable $e) {
            $this->failure();
            $this->failureNotificationTitle(__('Failed to cancel execution'));
            $this->failureNotificationBody($e->getMessage());
        }
    }

    public static function getDefaultName(): ?string
    {
        return 'run-execution';
    }
}
