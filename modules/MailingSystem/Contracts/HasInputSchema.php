<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Contracts;

interface HasInputSchema
{
    public function inputSchema(): array;
}
