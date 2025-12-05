<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Contracts;

use Epsicube\Support\Contracts\HasLabel;
use Epsicube\Support\Contracts\Registrable;

interface Mailer extends HasLabel, Registrable
{
    public function mailer(): \Illuminate\Contracts\Mail\Mailer;
}
