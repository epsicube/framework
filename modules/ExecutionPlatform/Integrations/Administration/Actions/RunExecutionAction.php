<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration\Actions;

use EpsicubeModules\ExecutionPlatform\Enum\ExecutionStatus;
use EpsicubeModules\ExecutionPlatform\Enum\Icons;
use EpsicubeModules\ExecutionPlatform\Models\Execution;
use Filament\Actions\Action;
use Throwable;

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
