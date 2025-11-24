<?php

declare(strict_types=1);

namespace UniGaleModules\MailingSystem;

use Composer\InstalledVersions;
use Illuminate\Contracts\Support\DeferrableProvider;
use UniGale\Foundation\Concerns\CoreModule;
use UniGale\Foundation\Contracts\HasIntegrations;
use UniGale\Foundation\IntegrationsManager;
use UniGaleModules\ExecutionPlatform\Facades\Activities;
use UniGaleModules\ExecutionPlatform\Facades\Workflows;
use UniGaleModules\MailingSystem\ExecutionEngine\Activities\SendMail as SendMailActivity;
use UniGaleModules\MailingSystem\ExecutionEngine\Workflows\SendMail as SendMailWorkflow;
use UniGaleModules\MailingSystem\Facades\Mailers;
use UniGaleModules\MailingSystem\Facades\Templates;
use UniGaleModules\MailingSystem\Mails\Mailer\LaravelMailer;
use UniGaleModules\MailingSystem\Mails\Templates\Blank;
use UniGaleModules\MailingSystem\Registries\MailersRegistry;
use UniGaleModules\MailingSystem\Registries\TemplatesRegistry;

class MailingSystemModule extends CoreModule implements DeferrableProvider, HasIntegrations
{
    protected function coreIdentifier(): string
    {
        return 'mailing-system';
    }

    public function name(): string
    {
        return __('Mailing System');
    }

    public function description(): ?string
    {
        return __('Mail delivery system, extensible and equipped with outbound message tracking.');
    }

    public function version(): string
    {
        return InstalledVersions::getPrettyVersion('unigale/framework')
            ?? InstalledVersions::getPrettyVersion('unigale/module-mailing-system');
    }

    public function provides(): array
    {
        return [Mailers::$accessor, Templates::$accessor];
    }

    public function register(): void
    {
        $this->app->singleton(Mailers::$accessor, function () {
            $registry = new MailersRegistry;
            $registry->register(...array_map(
                fn (string $name) => new LaravelMailer($name),
                array_keys(config()->array('mail.mailers', []))
            ));

            return $registry;
        });

        $this->app->singleton(Templates::$accessor, function () {
            $registry = new TemplatesRegistry;
            $registry->register(new Blank);

            return $registry;
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/email-templates', 'unigale-mail');
    }

    public function integrations(IntegrationsManager $integrations): void
    {
        $integrations->forModule(
            identifier: 'core::execution-platform',
            whenEnabled: function () {
                Activities::register(SendMailActivity::make());
                Workflows::register(SendMailWorkflow::make());
            }
        );
    }
}
