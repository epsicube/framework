<?php

namespace App\Modules;

use Epsicube\Schemas\Properties\BooleanProperty;
use Epsicube\Schemas\Schema;
use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Modules\Dependencies;
use Epsicube\Support\Modules\Identity;
use Epsicube\Support\Modules\Module;
use Illuminate\Support\ServiceProvider;

class MyCustomModule extends ServiceProvider implements IsModule
{
    public function module(): Module
    {
        return Module::make('custom::mycustommodule', '0.0.1')
            ->providers(static::class)
            ->identity(fn (Identity $identity) => $identity
                ->name('MyCustomModule')
                ->description('')
                ->author('Internal dev')
            )
            ->options(fn (Schema $options) => $options->append([
                'your-option' => BooleanProperty::make()
                    ->title('Your Option')
                    ->optional()
                    ->default(true),
            ]))->dependencies(function (Dependencies $dependencies) {
                $dependencies->module('target-module', 'target-version');
            });
    }

    /**
     * Register the module's services into the IoC container.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap the module after registration.
     */
    public function boot(): void
    {
        //
    }
}
