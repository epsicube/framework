<?php

declare(strict_types=1);

namespace App\Workflows\Activities;

class SimpleActivity
{
    public function run(array $input): string
    {
        return 'Processed '.($input['msg'] ?? 'nothing');
    }
}
