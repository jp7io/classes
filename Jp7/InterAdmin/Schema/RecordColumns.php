<?php

namespace Jp7\InterAdmin\Schema;

/** The system columns every record table shares, and how a date column is told from the rest. */
final class RecordColumns
{
    /**
     * `date_` means one thing, a tenant field of date type, and this closed list is its price:
     * every date branch consults both, or a system date stops being cast, normalised when absent
     * and writable as NULL. ⚠ `deleted_at` is NOT in it: the ORM reads an absent system date as
     * a truthy \Date, so `!$record->deleted_at` would say deleted.
     */
    public const SYSTEM_DATES = ['created_at', 'updated_at', 'publish_at', 'expire_at', 'hit_at'];

    public static function isDate(string $column): bool
    {
        return strpos($column, 'date_') === 0 || in_array($column, self::SYSTEM_DATES, true);
    }
}
