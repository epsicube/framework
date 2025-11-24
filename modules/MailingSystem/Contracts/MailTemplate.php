<?php

declare(strict_types=1);

namespace UniGaleModules\MailingSystem\Contracts;

use Illuminate\Mail\Mailables\Content;
use UniGale\Foundation\Contracts\HasLabel;
use UniGale\Foundation\Contracts\Registrable;

interface MailTemplate extends HasLabel, Registrable
{
    public function content(array $with = []): Content;
}
