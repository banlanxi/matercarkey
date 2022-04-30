<?php

return [
    'autoload' => false,
    'hooks' => [
        'app_init' => [
            'barcode',
            'qrcode',
        ],
    ],
    'route' => [
        '/barcode$' => 'barcode/index/index',
        '/barcode/build$' => 'barcode/index/build',
        '/qrcode$' => 'qrcode/index/index',
        '/qrcode/build$' => 'qrcode/index/build',
    ],
    'priority' => [],
    'domain' => '',
];
