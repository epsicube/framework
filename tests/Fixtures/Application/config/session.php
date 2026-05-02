<?php

declare(strict_types=1);

return [
    'driver'          => env('SESSION_DRIVER', 'array'),
    'lifetime'        => 120,
    'expire_on_close' => false,
    'encrypt'         => false,
    'files'           => storage_path('framework/sessions'),
    'cookie'          => 'epsicube-tests',
    'path'            => '/',
    'http_only'       => true,
    'same_site'       => 'lax',
];
