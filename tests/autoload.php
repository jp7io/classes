<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/Config.php';

if (!function_exists('config')) {
    function config($key = null, $default = null)
    {
        return Tests\Config::get($key, $default);
    }
}
