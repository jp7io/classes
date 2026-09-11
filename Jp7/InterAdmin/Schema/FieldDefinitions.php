<?php

namespace Jp7\InterAdmin\Schema;

use UnexpectedValueException;

/** `types.fields`: a type's field definitions, readable in both formats a tenant may store. */
class FieldDefinitions
{
    /** A definition's attributes, in the order the positional blob stored them. */
    const ATTRIBUTES = [
        'type', 'name', 'help', 'size', 'required', 'separator', 'xtra', 'list',
        'orderby', 'combo', 'readonly', 'form', 'label', 'permissions', 'default', 'name_id',
    ];

    // The flags, stored as real booleans. ⚠ Not `xtra`: `'S'` is one of its 30 values on ci, on
    // 1,988 rows, and a sweep reading it as a flag destroys every one of them silently.
    const BOOLEAN_ATTRIBUTES = ['required', 'separator', 'list', 'combo', 'readonly', 'form'];

    /**
     * The renamed xtra values, applied on the way OUT of both formats.
     * ⚠ Keyed by field type because `S` meant SIX things: MD5 on a password, no-time on a date,
     * HTML on a text, checked on a bool, and by-types on both selects.
     */
    const XTRA_RENAMES = [
        'varchar' => ['telefone' => 'phone', 'cor' => 'color', 'hora' => 'time'],
        'password' => ['S' => 'md5'],
        'file' => ['imagens' => 'images'],
        'date' => ['S' => 'notime'],
        'text' => ['S' => 'html'],
        'bool' => ['S' => 'checked'],
        'float' => ['moeda' => 'currency'],
        'select' => [
            'S' => 'types', 'radio' => 'records_radio', 'ajax' => 'records_ajax',
            'radio_tipos' => 'types_radio', 'ajax_tipos' => 'types_ajax',
        ],
        'select_multi' => ['S' => 'types', 'X' => 'records_search', 'X_tipos' => 'types_search'],
        'special' => [
            'registros' => 'records', 'registros_multi' => 'records_multi',
            'tipos' => 'types', 'tipos_multi' => 'types_multi',
        ],
    ];

    /**
     * The field type a column belongs to, as Field\Factory classifies it: the first segment, plus
     * `_multi` for a select_multi. ⚠ Not the type editor's `_<n>`/`_key` suffix strip, which
     * leaves a custom table's named column (`special_produtos`) unclassified.
     */
    public static function baseType(string $column): string
    {
        $base = explode('_', $column)[0];

        return $base === 'select' && strpos($column, 'select_multi_') === 0 ? 'select_multi' : $base;
    }

    /**
     * Definitions in the order the record form renders them, keyed by the row's position. JSON or
     * the positional `{;}`/`{,}` blob, told apart by the first character.
     * ⚠ A row keeps the attributes it stores rather than being padded: 96 of ci's predate
     * `name_id`, and an isset() on a later one has to answer as it did. encode() pads.
     */
    public static function decode(?string $fields): array
    {
        $fields = (string) $fields;

        if (trim($fields) === '') {
            return [];
        }

        $rows = ltrim($fields)[0] === '['
            ? self::decodeJson($fields)
            : self::decodePositional($fields);

        $rows = array_filter($rows, function (array $row): bool {
            return (string) ($row['type'] ?? '') !== '';
        });

        // Normalised on the way OUT, so the app reads the same values either side of a migration.
        return array_map([self::class, 'normalise'], $rows);
    }

    /** JSON padded to every attribute, in the order given, which IS the form's field order. */
    public static function encode(iterable $fields): string
    {
        $rows = [];

        foreach ($fields as $field) {
            $field = (array) $field;

            if (!strlen((string) ($field['type'] ?? ''))) {
                continue;
            }

            $row = [];
            foreach (self::ATTRIBUTES as $attribute) {
                $row[$attribute] = (string) ($field[$attribute] ?? '');
            }
            $rows[] = self::normalise($row);
        }

        // ⚠ No fields stores the EMPTY STRING, never `[]`: `holds_records` and the cache sync ask
        // strlen() whether a type configures any, and `[]` is 2.
        if (!$rows) {
            return '';
        }

        // Unescaped, so a tenant reading the column by hand sees the accents and slashes it typed.
        return json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * ⚠ `(bool)`, not `=== 'S'`: the grid posts `S`, a JSON row holds a real boolean, and both
     * formats spell false as the empty string.
     */
    private static function normalise(array $row): array
    {
        foreach (self::BOOLEAN_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $row)) {
                $row[$attribute] = (bool) $row[$attribute];
            }
        }

        if (array_key_exists('xtra', $row)) {
            // '0' and '' both mean "no xtra", and '' is the one an emptied input posts.
            $xtra = (string) $row['xtra'];
            $row['xtra'] = $xtra === '0'
                ? ''
                : (self::XTRA_RENAMES[self::baseType($row['type'] ?? '')][$xtra] ?? $xtra);
        }

        return $row;
    }

    private static function decodeJson(string $fields): array
    {
        $rows = json_decode($fields, true);

        if (!is_array($rows)) {
            throw new UnexpectedValueException('types.fields holds text that starts as JSON and does not parse.');
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    private static function decodePositional(string $fields): array
    {
        $rows = [];

        foreach (explode('{;}', $fields) as $i => $row) {
            $parameters = explode('{,}', $row);
            $mapped = [];

            foreach ($parameters as $j => $parameter) {
                if (isset(self::ATTRIBUTES[$j])) {
                    $mapped[self::ATTRIBUTES[$j]] = $parameter;
                }
            }

            $rows[$i] = $mapped;
        }

        return $rows;
    }

    /**
     * `column => name_id`: the name a RECORD answers to, which is not the one the blob stores.
     * ⚠ The suffix lands on a STORED name_id too: a select_ saying `moeda` answers to `moeda_id`.
     * ⚠ $typeName is the one impure step, needed by 4 of ci's 10,391 rows.
     * @param array $rows Field definitions, as decode() returns them.
     * @param callable $typeName Given a select_'s stored `name`, that type's own name.
     * @return array
     */
    public static function aliases(array $rows, callable $typeName): array
    {
        $aliases = [];

        foreach ($rows as $row) {
            $column = $row['type'];
            $alias = $row['name_id'] ?? '';

            if (!$alias) {
                $alias = self::aliasSource($row, $column, $typeName);

                if (!$alias) {
                    throw new UnexpectedValueException('An alias was expected.');
                }

                $alias = to_slug($alias, '_');
            }

            $aliases[$column] = $alias.self::aliasSuffix($column, $row);
        }

        return $aliases;
    }

    /**
     * A select_ stores the RELATED TYPE's id in `name`, so its alias reads off the field's label
     * or, failing that, off that type's name.
     * ⚠ Loose `!=` on purpose, as the derivation it came from: tightening it changes behaviour.
     */
    private static function aliasSource(array $row, string $column, callable $typeName)
    {
        $name = $row['name'] ?? '';

        if (strpos($column, 'select_') === 0 && $name != 'all') {
            return empty($row['label']) ? $typeName($name) : $row['label'];
        }

        return $name;
    }

    /** ⚠ Loose in_array on purpose, as the derivation it came from. */
    private static function aliasSuffix(string $column, array $row): string
    {
        if (strpos($column, 'select_') === 0) {
            return strpos($column, 'select_multi_') === 0 ? '_ids' : '_id';
        }

        if (strpos($column, 'special_') === 0 && ($row['xtra'] ?? '')) {
            return in_array($row['xtra'], self::getSpecialMultiXtras()) ? '_ids' : '_id';
        }

        return '';
    }

    /** A `tit_` or `func_` row renders on the form and backs no column: no record answers to it. */
    public static function isVirtualField(string $column): bool
    {
        return strpos($column, 'tit_') === 0 || strpos($column, 'func_') === 0;
    }

    /**
     * The xtra values of select_ fields which store types.
     * @return array
     */
    public static function getSelectTypeXtras(): array
    {
        return ['types', 'types_search', 'types_ajax', 'types_radio'];
    }

    /**
     * The xtra values of special_ fields which store types.
     * @return array
     */
    public static function getSpecialTypeXtras(): array
    {
        return ['types_multi', 'types'];
    }

    /**
     * The xtras of the special_ fields that store multiple records.
     * @return array
     */
    public static function getSpecialMultiXtras(): array
    {
        return ['records_multi', 'types_multi'];
    }
}
