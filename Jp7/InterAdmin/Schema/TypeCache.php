<?php

namespace Jp7\InterAdmin\Schema;

use Cache;
use DB;
use Illuminate\Cache\Repository;
use InterAdmin\Models\Type;
use PDOException;

/** The cache store every Type's derivations live in, whichever object model derives them. */
final class TypeCache
{
    // ⚠ ONE tag for both Types: FixOrphanType and the MFA migration flush this one, and a
    // second tag is a store none of their flushes reaches.
    public const TAG = 'type';

    /** Seconds, which Cache::remember() has taken since Laravel 5.8, never minutes. */
    public const TTL = 300;

    /** Seconds between two reads of the stamp, for every process of an app: one MAX() each. */
    public const CHECK_INTERVAL = 10;

    /** Its own keys, never the ORM's `modified`: two releases comparing one key flush each other. */
    public const STAMP_KEY = 'types_stamp';

    public const CHECKED_KEY = 'types_stamp:checked';

    private static bool $checked = false;

    private static bool $held = false;

    /** @var array<string, callable(): void> */
    private static array $listeners = [];

    /** The tag's store, checked against `types` at the first read of each unit of work. */
    public static function store(): Repository
    {
        if (!self::$checked && !self::$held) {
            self::$checked = true;
            self::check();
        }

        return Cache::tag(self::TAG);
    }

    /**
     * Flushes the tag when `types` moved outside this process (another app, the other color, a
     * raw write), as the ORM's checkCache() did: throttled through the STORE, so an app pays one
     * MAX(updated_at) per interval however many processes it runs.
     */
    public static function check(): void
    {
        $cache = Cache::tag(self::TAG);
        if ($cache->get(self::CHECKED_KEY) > time() - self::CHECK_INTERVAL) {
            return;
        }
        $cache->forever(self::CHECKED_KEY, time());

        // An unreachable database is BaseClassMap's to report, as "not connected": not from here.
        try {
            $stamp = (string) DB::table('types')->max('updated_at');
        } catch (PDOException) {
            return;
        }
        if ($stamp === $cache->get(self::STAMP_KEY)) {
            return;
        }

        // The flush takes both keys with it, so they are written again after it.
        self::flush();
        $cache->forever(self::STAMP_KEY, $stamp);
        $cache->forever(self::CHECKED_KEY, time());

        // Reported, never thrown: it runs inside whichever read came first, on any page.
        foreach (self::$listeners as $listener) {
            try {
                $listener();
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /** A write's forgets, which never wait on the stamp. */
    public static function forget(string ...$keys): void
    {
        $cache = Cache::tag(self::TAG);
        foreach ($keys as $key) {
            $cache->forget($key);
        }
    }

    /** Everything derived from `types`, in the store and in this process. */
    public static function flush(): void
    {
        Cache::tag(self::TAG)->flush();
        // Singletons a queue worker keeps across jobs, holding the maps the flush just dropped.
        RecordClassMap::getInstance()->clearCache();
        TypeClassMap::getInstance()->clearCache();
        Type::forgetTypeState();
    }

    /** Runs after a flush the stamp caused. Keyed, so a boot per test replaces it rather than stacking it. */
    public static function whenTypesChange(string $name, callable $listener): void
    {
        self::$listeners[$name] = $listener;
    }

    /**
     * For a test harness, set BEFORE the app boots: the boot's first autoload miss asks the class
     * maps, which checks the stamp, and a repair it sets off commits outside any test transaction.
     */
    public static function hold(bool $held = true): void
    {
        self::$held = $held;
    }

    /** The unit-of-work boundary, which Models\Type::forgetTypeState() crosses. */
    public static function forgetCheck(): void
    {
        self::$checked = false;
    }
}
