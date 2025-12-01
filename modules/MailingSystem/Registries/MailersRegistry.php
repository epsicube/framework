<?php

declare(strict_types=1);

namespace UniGaleModules\MailingSystem\Registries;

use UniGale\Support\Registry;
use UniGaleModules\MailingSystem\Contracts\Mailer;

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
