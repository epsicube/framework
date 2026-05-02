<?php

declare(strict_types=1);

return [
    'default' => env('CACHE_STORE', 'array'),
    'stores'  => [
        'array' => [
            'driver'    => 'array',
            'serialize' => false,
        ],
    ],
    'prefix' => 'epsicube-tests',
];
