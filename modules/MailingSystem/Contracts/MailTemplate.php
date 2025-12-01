<?php

declare(strict_types=1);

namespace UniGaleModules\MailingSystem\Contracts;

use Illuminate\Mail\Mailables\Content;
use UniGale\Support\Contracts\HasLabel;
use UniGale\Support\Contracts\Registrable;

interface MailTemplate extends HasLabel, Registrable
{
    public function content(array $with = []): Content;
}
