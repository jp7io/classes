<?php

namespace Tests;

use Illuminate\Support\Arr;

/**
 * Stands in for the config() a host Laravel app provides. Jp7\ classes read it through the
 * global function with no injection point, so the suite declares that function (in
 * tests/autoload.php, which is unnamespaced) and set() swaps the array it answers from.
 */
class Config
{
    private static array $items = [];

    public static function set(array $items): void
    {
        self::$items = $items;
    }

    public static function get(?string $key = null, $default = null)
    {
        return $key === null ? self::$items : Arr::get(self::$items, $key, $default);
    }
}
