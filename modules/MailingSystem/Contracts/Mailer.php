<?php

declare(strict_types=1);

namespace UniGaleModules\MailingSystem\Contracts;

use UniGale\Foundation\Contracts\HasLabel;
use UniGale\Foundation\Contracts\Registrable;

interface Mailer extends HasLabel, Registrable
{
    public function mailer(): \Illuminate\Contracts\Mail\Mailer;
}
