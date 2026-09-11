<?php

namespace Jp7\InterAdmin\Schema;

use UnexpectedValueException;

/** `types.children`: the child types a record's form offers as tabs, in either stored format. */
class ChildDeclarations
{
    /** A declaration's attributes, in the order the positional blob stored them. */
    const ATTRIBUTES = ['type_id', 'name', 'help', 'grandchildren'];

    /** The one FLAG among them, stored as a real boolean. */
    const BOOLEAN_ATTRIBUTES = ['grandchildren'];

    /**
     * Declarations in the order the tabs are drawn, from JSON or the positional
     * `<type_id>{,}<name>{,}<help>{,}<grandchildren>{;}` blob, told apart by the first character.
     * ⚠ A row is padded to all four attributes: four of ci's 702 declarations carry three, and
     * reading `help` on one has to answer the empty string.
     * @return array<int, array{type_id: string, name: string, help: string, grandchildren: bool}>
     */
    public static function decode(?string $children): array
    {
        $children = (string) $children;

        if (trim($children) === '') {
            return [];
        }

        $rows = ltrim($children)[0] === '['
            ? self::decodeJson($children)
            : self::decodePositional($children);

        $rows = array_filter($rows, function (array $row): bool {
            return (string) ($row['type_id'] ?? '') !== '';
        });

        return array_values(array_map([self::class, 'normalise'], $rows));
    }

    /** JSON in the order given, because declaration order IS the order the tabs are drawn. */
    public static function encode(iterable $children): string
    {
        $rows = [];

        foreach ($children as $child) {
            $child = (array) $child;

            if (!strlen((string) ($child['type_id'] ?? ''))) {
                continue;
            }

            $row = [];
            foreach (self::ATTRIBUTES as $attribute) {
                $row[$attribute] = $child[$attribute] ?? '';
            }
            $rows[] = self::normalise($row);
        }

        // ⚠ No children stores the EMPTY STRING, never `[]`: Box\Model\TypeAbstract and the seeded
        // fixtures ask whether the column is `''`, and `[]` is 2 characters of yes.
        if (!$rows) {
            return '';
        }

        return json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * ⚠ `<> ''`, never `=== 'S'`: the blob spelled the flag `S`, JSON holds a real boolean, and the
     * form posts `1`. `type_id` stays a STRING, as the positional reader handed it back.
     */
    private static function normalise(array $row): array
    {
        foreach (self::ATTRIBUTES as $attribute) {
            $value = $row[$attribute] ?? '';
            $row[$attribute] = in_array($attribute, self::BOOLEAN_ATTRIBUTES, true)
                ? (bool) $value
                : (string) $value;
        }

        return $row;
    }

    /**
     * SQL matching the types whose `children` DECLARES $typeId in either format: a JSON pattern,
     * `%}<id>{%` for a positional declaration after the first, and `<id>{%` for the first one.
     * ⚠ Both over-match a NAME that is bare digits, which callers tolerate: an extra type in an
     * option list, never a missing one.
     */
    public static function declaresSql(string $column, $typeId): string
    {
        $typeId = (int) $typeId;

        return '('.$column.' LIKE \'%"type_id":"'.$typeId.'"%\''
            .' OR '.$column.' LIKE \'%}'.$typeId.'{%\''
            .' OR '.$column.' LIKE \''.$typeId.'{%\')';
    }

    private static function decodeJson(string $children): array
    {
        $rows = json_decode($children, true);

        if (!is_array($rows)) {
            throw new UnexpectedValueException('types.children holds text that starts as JSON and does not parse.');
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    /**
     * ⚠ The blob is separated AND terminated by `{;}`, so its last split is the empty tail. The
     * empty-`type_id` filter drops it, which also keeps the last declaration of an unterminated one.
     */
    private static function decodePositional(string $children): array
    {
        $rows = [];

        foreach (explode('{;}', $children) as $entry) {
            $parameters = explode('{,}', $entry);
            $row = [];

            foreach (self::ATTRIBUTES as $position => $attribute) {
                $row[$attribute] = $parameters[$position] ?? '';
            }

            $rows[] = $row;
        }

        return $rows;
    }
}
