<?php

declare(strict_types=1);

namespace EpsicubeModules\JsonRpcServer\Concerns;

use Epsicube\Support\Contracts\Registrable;

// TODO use identifier and allow instance instead of class-string
abstract class Procedure extends \Sajya\Server\Procedure implements Registrable
{
    public function identifier(): string
    {
        return static::$name;
    }
}
