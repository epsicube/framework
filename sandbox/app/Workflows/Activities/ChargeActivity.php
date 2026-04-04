<?php

declare(strict_types=1);

namespace App\Workflows\Activities;

class ChargeActivity
{
    public function run(array $input): string
    {
        $amount = $input['amount'] ?? 0;

        return "Charging of {$amount}€ completed.";
    }
}
