<?php

declare(strict_types=1);

namespace App\Workflows\Activities;

use Exception;

class FlakyActivity
{
    public function run(array $input): string
    {
        return 'FLaky OK';
        $chance = rand(1, 100);

        if ($chance <= 50) {
            throw new Exception("Flaky activity failure (Chance: {$chance}/100)");
        }

        return "Flaky activity success ! (Chance: {$chance}/100)";
    }
}
