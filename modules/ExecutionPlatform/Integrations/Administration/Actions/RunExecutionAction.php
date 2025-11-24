<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Integrations\Administration\Actions;

use Filament\Actions\Action;
use Throwable;
use UniGaleModules\ExecutionPlatform\Enum\ExecutionStatus;
use UniGaleModules\ExecutionPlatform\Enum\Icons;
use UniGaleModules\ExecutionPlatform\Models\Execution;

class RunExecutionAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->label(__('Run'));
        $this->icon(Icons::RUN);
        $this->color('success');

        $this->hidden(fn (Execution $record) => $record->status !== ExecutionStatus::QUEUED);

        $this->action(fn (array $data, Execution $record) => $this->executeRun($record));
    }

    public function executeRun(Execution $record): void
    {
        try {
            $record->run();
            $this->success();
            $this->successNotificationTitle(__('Execution started successfully'));
        } catch (Throwable $e) {
            $this->failure();
            $this->failureNotificationTitle(__('Failed to start execution'));
            $this->failureNotificationBody($e->getMessage());
        }
    }

    public static function getDefaultName(): ?string
    {
        return 'execution-run';
    }
}
