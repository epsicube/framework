<?php

declare(strict_types=1);

namespace UniGaleModules\MailingSystem\Contracts;

use UniGale\Support\Contracts\HasLabel;
use UniGale\Support\Contracts\Registrable;

interface Mailer extends HasLabel, Registrable
{
    public function mailer(): \Illuminate\Contracts\Mail\Mailer;
}
