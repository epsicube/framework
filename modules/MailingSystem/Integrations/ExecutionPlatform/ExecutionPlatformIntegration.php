<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\ExecutionPlatform;

use EpsicubeModules\ExecutionPlatform\Facades\Activities;
use EpsicubeModules\ExecutionPlatform\Facades\Workflows;
use EpsicubeModules\MailingSystem\Integrations\ExecutionPlatform\Activities\ListMailers;
use EpsicubeModules\MailingSystem\Integrations\ExecutionPlatform\Activities\ListTemplates;
use EpsicubeModules\MailingSystem\Integrations\ExecutionPlatform\Activities\SendMail as SendMailActivity;
use EpsicubeModules\MailingSystem\Integrations\ExecutionPlatform\Workflows\SendMail as SendMailWorkflow;

class ExecutionPlatformIntegration
{
    public static function handle(): void
    {
        Activities::register(
            new SendMailActivity,
            new ListTemplates,
            new ListMailers
        );
        Workflows::register(new SendMailWorkflow);
    }
}
