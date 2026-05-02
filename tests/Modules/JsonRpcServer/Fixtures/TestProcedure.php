<?php

declare(strict_types=1);

namespace Epsicube\Tests\Modules\JsonRpcServer\Fixtures;

use EpsicubeModules\JsonRpcServer\Concerns\Procedure;

final class TestProcedure extends Procedure
{
    public static string $name = 'tests.ping';
}
