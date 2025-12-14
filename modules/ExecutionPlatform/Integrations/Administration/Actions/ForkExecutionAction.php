<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration\Actions;

use EpsicubeModules\ExecutionPlatform\Enum\ExecutionStatus;
use EpsicubeModules\ExecutionPlatform\Enum\Icons;
use EpsicubeModules\ExecutionPlatform\Models\Execution;
use Filament\Actions\Action;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Throwable;

class ForkExecutionAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->label(__('Fork'));
        $this->icon(Icons::FORK);
        $this->color('info');
        $this->schema([
            ToggleButtons::make('run_mode')
                ->hiddenLabel()
                ->inline()->grouped()
                ->options([
                    'queued' => __('Keep Queued'),
                    'run'    => __('Run Immediately'),
                ])->colors([
                    'queued' => 'warning',
                    'run'    => 'success',
                ])->default('run')->required(),
        ]);
        $this->action(fn (array $data, Execution $record) => $this->fork($data, $record));
    }

    protected function fork(array $data, Execution $record): void
    {
        try {
            $newExecution = DB::transaction(function () use ($record) {
                // Clone the execution and reset specific fields
                $clone = $record->replicate([
                    'started_at',
                    'completed_at',
                    '_run_id',
                    '_idempotency_key',
                    'last_error',
                ])->fill([
                    'status' => ExecutionStatus::QUEUED,
                ]);

                $clone->save();

                return $clone;
            });
        } catch (Throwable $e) {
            $this->failure();
            $this->failureNotificationTitle(__('Error forking execution'));
            $this->failureNotificationBody($e->getMessage());

            return;
        }

        // Stop before running if not in run mode
        if ($data['run_mode'] !== 'run') {
            $this->success();
            $this->successNotificationTitle(__('Execution forked successfully'));
            $this->successNotification(function (Notification $notification) use ($newExecution) {
                $notification->body(__('New Execution ID: :id', ['id' => $newExecution->id]));
            });

            return;
        }

        try {
            $newExecution->run();

            $this->success();
            $this->successNotificationTitle(__('Execution forked and ran successfully'));
            $this->successNotification(function (Notification $notification) use ($newExecution) {
                $notification->body(__('New Execution ID: :id', ['id' => $newExecution->id]));
            });
        } catch (Throwable $e) {
            $this->failure();
            $this->failureNotificationTitle(__('Error running forked execution'));
            $this->failureNotificationBody($e->getMessage());
        }
    }

    public static function getDefaultName(): ?string
    {
        return 'fork-execution';
    }
}
