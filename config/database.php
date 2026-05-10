<?php

$databasePath = __DIR__ . '/../storage/database/app.sqlite';

return [
    'driver' => 'sqlite',
    'database' => $databasePath,
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => $databasePath,
            'foreign_keys' => true,
        ],
    ],
];
