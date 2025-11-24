<?php

declare(strict_types=1);

namespace UniGaleModules\MailingSystem\Registries;

use UniGale\Foundation\Concerns\Registry;
use UniGaleModules\MailingSystem\Contracts\MailTemplate;

/**
 * @extends Registry<MailTemplate>
 */
class TemplatesRegistry extends Registry
{
    public function getRegistrableType(): string
    {
        return MailTemplate::class;
    }
}
