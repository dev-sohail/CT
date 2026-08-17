<?php

return [
    'defaults' => [
        'guard' => 'web',
        'expiration' => null,
        'middleware' => [
            'verify' => 'verified',
            'throttle' => 'throttle:api',
        ],
    ],
    'guards' => [
        'api' => [
            'driver' => 'sanctum',
        ],
    ],
];
