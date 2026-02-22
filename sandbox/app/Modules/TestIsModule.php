<?php

declare(strict_types=1);

namespace App\Modules;

use Epsicube\Schemas\Properties\BooleanProperty;
use Epsicube\Schemas\Schema;
use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Enums\ModuleCondition;
use Epsicube\Support\Modules\Condition;
use Epsicube\Support\Modules\Dependencies;
use Epsicube\Support\Modules\Identity;
use Epsicube\Support\Modules\Module;
use Epsicube\Support\Modules\Requirements;
use Epsicube\Support\Modules\Support;
use Epsicube\Support\Modules\Supports;
use Illuminate\Support\ServiceProvider;

class TestIsModule extends ServiceProvider implements IsModule
{
    public function module(): Module
    {
        return Module::make('inventory-manager', '1.0.0')
            ->providers(static::class)
            ->identity(fn (Identity $identity) => $identity
                ->name('Inventory Manager')
                ->description('Inventory Manager')
                ->author('Epsicube')
            )
            ->dependencies(fn (Dependencies $dependencies) => $dependencies
                ->module('core::administration', '*')
            )
            ->requirements(fn (Requirements $requirements) => $requirements->add(
                //                Condition::epsicubeVersion('>=10'),
                Condition::phpVersion('>= 8.2'),
                Condition::phpExtensions('curl'),

                Condition::any(
                    Condition::phpExtensions('aws-sdk-php'),
                    Condition::phpExtensions('azure-storage-blob'),
                    Condition::all(
                        Condition::phpExtensions('fileinfo'),
                        Condition::databaseDrivers('pgsql') // Fallback local
                    )
                ),

                ...Condition::when(Condition::phpExtensions('curl'), [
                    Condition::phpVersion('>= 8.3'),
                ])
            ))
            ->supports(fn (Supports $supports) => $supports->add(
                Support::forModule(
                    ['module1' => 'version', 'module2' => 'version'],
                    whenPass: fn () => $this->enableRealTimeSync(...),
                    //                    whenFail: fn () => $this->enablePollingSync(...),
                    state: ModuleCondition::PRESENT,
                ),
                Support::for(
                    Condition::all(
                        Condition::phpExtensions('inotify'),
                        Condition::phpExtensions('curl'),
                    ),
                    whenPass: fn () => $this->enableRealTimeSync(...),
                    //                    whenFail: fn () => $this->enablePollingSync(...),
                    whenSkipped: fn () => $this->enablePollingSync(...),
                ),
            ))
            ->options(fn (Schema $options) => $options->append([
                'your-option' => BooleanProperty::make()
                    ->title('Your Option')
                    ->optional()
                    ->default(true),
            ]));
    }

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
