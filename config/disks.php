<?php

return [
    'disks' => [
        'imagex' => [
            'driver' => 'imagex',
            'region' => env('IMAGEX_ACCESS_KEY', 'cn-north-1'),
            'access_key' => env('IMAGEX_ACCESS_KEY', ''),
            'secret_key' => env('IMAGEX_SECRET_KEY', ''),
            'service_id' => env('IMAGEX_BUCKET', ''),
            'domain' => env('IMAGEX_DOMAIN', 'example.com'),
        ],
    ],
];
