<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use Epsicube\Schemas\Schema;
use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Modules\Identity;
use Epsicube\Support\Modules\Module;
use Epsicube\Support\Modules\Support;
use Epsicube\Support\Modules\Supports;
use EpsicubeModules\MailingSystem\Facades\Mailers;
use EpsicubeModules\MailingSystem\Facades\Templates;
use EpsicubeModules\MailingSystem\Integrations\ExecutionPlatform\ExecutionPlatformIntegration;
use EpsicubeModules\MailingSystem\Mails\Mailer\LaravelMailer;
use EpsicubeModules\MailingSystem\Mails\Templates\Blank;
use EpsicubeModules\MailingSystem\Mails\Templates\Html;
use EpsicubeModules\MailingSystem\Registries\MailersRegistry;
use EpsicubeModules\MailingSystem\Registries\TemplatesRegistry;
use Illuminate\Contracts\Support\DeferrableProvider;

class MailingSystemModule extends ServiceProvider implements DeferrableProvider, IsModule
{
    public function module(): Module
    {
        return Module::make(
            identifier: 'core::mailing-system',
            version: InstalledVersions::getVersion('epsicube/framework')
                ?? InstalledVersions::getVersion('epsicube/module-mailing-system')
        )
            ->providers(static::class)
            ->identity(fn (Identity $identity) => $identity
                ->name(__('Mailing System'))
                ->author('Core Team')
                ->description(__('Mail delivery system, extensible and equipped with outbound message tracking.'))
            )
            ->supports(fn (Supports $supports) => $supports->add(
                Support::forModule('core::execution-platform', ExecutionPlatformIntegration::handle(...)),
            ))->options(fn (Schema $schema) => $schema->append(
                MailingSystemOptions::definition(),
            ));
    }

    public function provides(): array
    {
        return [Mailers::$accessor, Templates::$accessor];
    }

    public function register(): void
    {
        $this->app->singleton(Mailers::$accessor, function () {
            $registry = new MailersRegistry;
            if (MailingSystemOptions::withInternalMailers()) {
                $registry->register(...array_map(
                    fn (string $name) => new LaravelMailer($name),
                    array_keys(config()->array('mail.mailers', []))
                ));
            }

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
        $this->loadViewsFrom(__DIR__.'/resources/email-templates', 'epsicube-mail');
    }
}
