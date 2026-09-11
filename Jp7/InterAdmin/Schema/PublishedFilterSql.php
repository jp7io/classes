<?php

namespace Jp7\InterAdmin\Schema;

/** The "is this row visible now?" predicates a query ANDs in, per kind of table. */
final class PublishedFilterSql
{
    /**
     * Ends in 'AND ' deliberately: every caller concatenates it onto the front of its own clause.
     * ⚠ Only a PREFIXED `types`/`tags` takes its own branch, and a bare one falls through to the
     * records calendar, on columns it does not have. Every caller passes a prefixed name.
     * @param int $now Record::getTimestamp(), the clock a test can freeze.
     * @param bool $preview The admin's mode, which also admits unpublished rows and any child row.
     * @return string|null Null for a table with nothing to filter: tags.
     */
    public static function build(string $table, string $alias, int $now, bool $preview): ?string
    {
        $tableParts = explode('_', $table);
        $table = end($tableParts);

        if ($table === 'tags' && count($tableParts) === 3) {
            return null;
        }

        if (($table === 'types' && count($tableParts) === 3) || $table === 'files') {
            return $alias.'.visible = 1 AND '.$alias.'.deleted_at IS NULL AND ';
        }

        return self::records($alias, $now, $preview);
    }

    /**
     * Published already, not yet expired, flagged visible, not soft-deleted.
     * ⚠ BOTH dates take the IS NULL arm: an absent publish date means published, and
     * `NULL <= now()` is UNKNOWN, dropping the row with no error. That cost ci 6,290 live rows.
     */
    private static function records(string $alias, int $now, bool $preview): string
    {
        $filter = '('.$alias.".publish_at <= '".date('Y-m-d H:i:59', $now)."' OR ".$alias.
                '.publish_at IS NULL)'.
            ' AND ('.$alias.".expire_at > '".date('Y-m-d H:i:00', $now)."' OR ".$alias.
                '.expire_at IS NULL)'.
            ' AND '.$alias.'.bool_key = 1'.
            ' AND '.$alias.'.deleted_at IS NULL'.
            ' AND ';

        return $preview ? $filter.'('.$alias.'.publish = 1 OR '.$alias.'.parent_id > 0) AND ' : $filter;
    }
}
