<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Contracts;

use Epsicube\Support\Contracts\HasLabel;
use Epsicube\Support\Contracts\Registrable;
use Illuminate\Mail\Mailables\Content;

interface MailTemplate extends HasLabel, Registrable
{
    public function content(array $with = []): Content;
}
