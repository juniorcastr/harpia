<?php

return [
    'polling' => [
        'enabled' => (bool) env('PONTO_POLLING_ENABLED', false),
        'cron' => env('PONTO_POLLING_CRON', '*/15 * * * *'),
        'limit' => (int) env('PONTO_POLLING_LIMIT', 500),
    ],
];
