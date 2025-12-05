<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Registries;

use Epsicube\Support\Registry;
use EpsicubeModules\MailingSystem\Contracts\Mailer;

/**
 * @extends Registry<Mailer>
 */
class MailersRegistry extends Registry
{
    public function getRegistrableType(): string
    {
        return Mailer::class;
    }
}
