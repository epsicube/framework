<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Registries;

use Epsicube\Support\Registry;
use EpsicubeModules\MailingSystem\Contracts\MailTemplate;

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
