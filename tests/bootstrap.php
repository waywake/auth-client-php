<?php

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/Support/LumenApplication.php';

if (!function_exists('isDev')) {
    function isDev(): bool
    {
        return env('APP_ENV') !== 'production';
    }
}
