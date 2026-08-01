<?php

use Illuminate\Support\Str;

define('BASE_PATH', __DIR__);

require __DIR__ . '/../vendor/autoload.php';

/*
 * jp7io/classes-deprecated still calls starts_with()/ends_with() -- Laravel 5 helpers deleted
 * in Laravel 6. In production they resolve only because a *client* package happens to ship a
 * polyfill (_config/ci/vendor/jp7io/ci-intranet/app/legacy.php), so any tenant without that
 * file fatals. The real fix is Str::startsWith/Str::endsWith in classes-deprecated itself,
 * which is a separate repo mid-migration; this keeps the suite honest until then.
 *
 * classes' own code no longer calls any of them -- do not add to this list to make new code
 * pass.
 */
if (!function_exists('starts_with')) {
    function starts_with($haystack, $needles)
    {
        return Str::startsWith($haystack, $needles);
    }
}

if (!function_exists('ends_with')) {
    function ends_with($haystack, $needles)
    {
        return Str::endsWith($haystack, $needles);
    }
}
