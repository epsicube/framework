<?php

declare(strict_types=1);

namespace UniGaleModules\MailingSystem\Contracts;

interface HasInputSchema
{
    public function inputSchema(): array;
}
