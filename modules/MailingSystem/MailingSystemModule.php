<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Modules\Identity;
use Epsicube\Support\Modules\Module;
use Epsicube\Support\Modules\Support;
use Epsicube\Support\Modules\Supports;
use EpsicubeModules\MailingSystem\Contracts\Driver;
use EpsicubeModules\MailingSystem\Facades\Drivers;
use EpsicubeModules\MailingSystem\Facades\Templates;
use EpsicubeModules\MailingSystem\Integrations\Administration\AdministrationIntegration;
use EpsicubeModules\MailingSystem\Integrations\ExecutionPlatform\ExecutionPlatformIntegration;
use EpsicubeModules\MailingSystem\Listeners\MessageTrackingSubscriber;
use EpsicubeModules\MailingSystem\Mails\Drivers\LaravelDriver;
use EpsicubeModules\MailingSystem\Mails\Drivers\Mailjet\MailjetServiceProvider;
use EpsicubeModules\MailingSystem\Mails\Drivers\MailjetDriver;
use EpsicubeModules\MailingSystem\Mails\Drivers\SendGridDriver;
use EpsicubeModules\MailingSystem\Mails\Templates\Blank;
use EpsicubeModules\MailingSystem\Mails\Templates\Html;
use EpsicubeModules\MailingSystem\Mails\TrackedTransport;
use EpsicubeModules\MailingSystem\Models\Mailer as MailerModel;
use EpsicubeModules\MailingSystem\Registries\DriversRegistry;
use EpsicubeModules\MailingSystem\Registries\TemplatesRegistry;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Event;

class MailingSystemModule extends ServiceProvider implements IsModule
{
    public function module(): Module
    {
        return Module::make(
            identifier: 'core::mailing-system',
            version: InstalledVersions::getVersion('epsicube/framework')
            ?? InstalledVersions::getVersion('epsicube/module-mailing-system')
        )
            ->providers(
                static::class,
                MailjetServiceProvider::class,
            )
            ->identity(fn (Identity $identity) => $identity
                ->name(__('Mailing System'))
                ->author('Core Team')
                ->description(__('Mail delivery system, extensible and equipped with outbound message tracking.'))
            )
            ->supports(fn (Supports $supports) => $supports->add(
                Support::forModule('core::execution-platform', ExecutionPlatformIntegration::handle(...)),
                Support::forModule('core::administration', AdministrationIntegration::handle(...)),
            ))->options(MailingSystemOptions::configure(...));
    }

    public function register(): void
    {
        $this->app->singleton(Drivers::$accessor, function () {
            $registry = new DriversRegistry;
            $registry->register(new LaravelDriver, new MailjetDriver, new SendGridDriver);

            return $registry;
        });

        $this->app->singleton(Templates::$accessor, function () {
            $registry = new TemplatesRegistry;
            $registry->register(new Blank, new Html);

            return $registry;
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes/webhook.php');
        $this->loadViewsFrom(__DIR__.'/resources', 'epsicube-mail');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        Event::subscribe(MessageTrackingSubscriber::class);

        Mailer::macro('track', function (MailerModel $model, Driver $driver) {
            /** @var Mailer $this */
            $this->setSymfonyTransport(new TrackedTransport($this->getSymfonyTransport(), $model, $driver));

            return $this;
        });

    }
}
