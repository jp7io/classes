<?php

namespace Jp7\Laravel;

use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\FileStore;
use Illuminate\Filesystem\Filesystem;

class CacheExtension
{
    public static function apply()
    {
        // FileStore doesn't have tags()
        Cache::macro('tag', function (string $tag) {
            static $instances; // memoize
            if (!isset($instances[$tag])) {
                $defaultRepo = Cache::store();
                $store = $defaultRepo->getStore();
                if (!$store instanceof FileStore) {
                    return $defaultRepo->tags($tag);
                }
                $tagStore = new FileStore(
                    $store->getFilesystem(),
                    $store->getDirectory() . '/_tag_' . $tag
                );
                $instances[$tag] = Cache::repository($tagStore);
            }
            return $instances[$tag];
        });
    }
}
