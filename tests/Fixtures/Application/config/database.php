<?php

declare(strict_types=1);

return [
    'default'     => env('DB_CONNECTION', 'sqlite'),
    'connections' => [
        'sqlite' => [
            'driver'                  => 'sqlite',
            'database'                => env('DB_DATABASE', ':memory:'),
            'prefix'                  => '',
            'foreign_key_constraints' => true,
        ],
    ],
    'migrations' => 'migrations',
];
