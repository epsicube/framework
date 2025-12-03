<?php

declare(strict_types=1);

namespace UniGaleModules\MailingSystem\Integrations\ExecutionPlatform;

use UniGaleModules\ExecutionPlatform\Facades\Activities;
use UniGaleModules\ExecutionPlatform\Facades\Workflows;
use UniGaleModules\MailingSystem\Integrations\ExecutionPlatform\Activities\ListMailers;
use UniGaleModules\MailingSystem\Integrations\ExecutionPlatform\Activities\ListTemplates;
use UniGaleModules\MailingSystem\Integrations\ExecutionPlatform\Activities\SendMail as SendMailActivity;
use UniGaleModules\MailingSystem\Integrations\ExecutionPlatform\Workflows\SendMail as SendMailWorkflow;

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
