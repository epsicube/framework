<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Plans;

use Epsicube\Support\Contracts\ActivationDriver;
use Epsicube\Support\Enums\ModuleStatus;
use Epsicube\Support\Facades\Modules;
use Epsicube\Support\Modules\Module;
use Epsicube\Support\Plan;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * @extends Plan<Module>
 */
class ModuleDeactivationPlan extends Plan
{
    public function __construct(protected ActivationDriver $driver)
    {
        parent::__construct();
    }

    protected function setUp(): void
    {
        $this->addTask('Ensure module can be deactivated', function (Module $module) {
            if (! Modules::canBeDisabled($module)) {
                throw new RuntimeException(__('This module cannot be deactivated.'));
            }
        }, -2);

        $this->addTask('Mark module as disabled', function (Module $module) {
            $this->driver->disable($module);
            $module->status = ModuleStatus::DISABLED;
        }, -1);

        $this->addTask(__('Clear cache'), function () {
            $process = $this->callArtisanCommand('optimize:clear');
            if (! $process->successful()) {
                Log::error('Failed to clear cache', ['output' => $process->errorOutput()]);
            }
        });

        if (app()->routesAreCached()) {
            $this->addTask(__('Generate cache'), function () {
                $process = $this->callArtisanCommand('optimize');
                if (! $process->successful()) {
                    Log::error('Failed to generate cache', ['output' => $process->errorOutput()]);
                }
            });
        }
        $this->addTask(__('Terminate worker'), function () {
            $process = $this->callArtisanCommand('epsicube:terminate');
            if (! $process->successful()) {
                Log::error('Failed to send terminate signal', ['output' => $process->errorOutput()]);
            }
        });
    }
}
