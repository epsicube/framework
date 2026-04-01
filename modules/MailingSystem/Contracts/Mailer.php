<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Contracts;

use Epsicube\Support\Contracts\HasLabel;
use Epsicube\Support\Contracts\Registrable;
use EpsicubeModules\MailingSystem\Mails\EpsicubeMail;
use EpsicubeModules\MailingSystem\Models\Mail;

interface Mailer extends HasLabel, Registrable
{
    public function mailer(): \Illuminate\Contracts\Mail\Mailer;

    public function configureMail(EpsicubeMail &$mail, Mail $model): void;

}
