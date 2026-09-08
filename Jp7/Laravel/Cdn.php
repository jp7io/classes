<?php

namespace Jp7\Laravel;

class Cdn
{
    public static function asset($url, $version = false)
    {
        return self::replace(asset($url)).
            ($version ? '?v='.self::getVersion() : '');
    }

    private static function replace($url)
    {
        if ($cdn = config('cdn.url')) {
            $url = replace_prefix(config('app.url'), $cdn, $url);
        }
        return $url;
    }

    private static function getVersion()
    {
        // Using contents of .version as version number
        return trim(file_get_contents(base_path('.version')));
    }
}
