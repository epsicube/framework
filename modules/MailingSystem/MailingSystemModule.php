<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use Epsicube\Schemas\Schema;
use Epsicube\Support\Contracts\HasIntegrations;
use Epsicube\Support\Contracts\HasOptions;
use Epsicube\Support\Contracts\Module;
use Epsicube\Support\Integrations;
use Epsicube\Support\ModuleIdentity;
use EpsicubeModules\MailingSystem\Facades\Mailers;
use EpsicubeModules\MailingSystem\Facades\Templates;
use EpsicubeModules\MailingSystem\Integrations\ExecutionPlatform\ExecutionPlatformIntegration;
use EpsicubeModules\MailingSystem\Mails\Mailer\LaravelMailer;
use EpsicubeModules\MailingSystem\Mails\Templates\Blank;
use EpsicubeModules\MailingSystem\Mails\Templates\Html;
use EpsicubeModules\MailingSystem\Registries\MailersRegistry;
use EpsicubeModules\MailingSystem\Registries\TemplatesRegistry;
use Illuminate\Contracts\Support\DeferrableProvider;

class MailingSystemModule extends ServiceProvider implements DeferrableProvider, HasIntegrations, HasOptions, Module
{
    public function identifier(): string
    {
        return 'core::mailing-system';
    }

    public function identity(): ModuleIdentity
    {
        return ModuleIdentity::make(
            name: __('Mailing System'),
            version: InstalledVersions::getPrettyVersion('epsicube/framework')
            ?? InstalledVersions::getPrettyVersion('epsicube/module-mailing-system'),
            author: 'Core Team',
            description: __('Mail delivery system, extensible and equipped with outbound message tracking.')
        );
    }

    public function options(Schema $schema): void
    {
        $schema->append(MailingSystemOptions::definition());
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

    public function integrations(): Integrations
    {
        return Integrations::make()->forModule(
            identifier: 'core::execution-platform',
            whenEnabled: [ExecutionPlatformIntegration::class, 'handle']
        );
    }
}
