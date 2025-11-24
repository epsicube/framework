<?php

declare(strict_types=1);

namespace UniGale\Foundation\Contracts;

use UniGale\Foundation\IntegrationsManager;

interface HasIntegrations
{
    public function integrations(IntegrationsManager $integrations): void;
}
