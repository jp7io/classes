<?php

namespace Jp7\InterAdmin\Field;

/** A field definition's header text, as the legacy ORM's FieldUtil::getFieldHeader() answered it. */
final class FieldHeader
{
    /**
     * A special_ or func_ asks its handler in header mode, a select_ its label and then the type
     * it points at, whichever object model built the row; anything else is its name.
     * @param array<string, mixed> $field A getFields() row.
     */
    public static function text(array $field): mixed
    {
        $key = $field['type'];
        if (str_starts_with($key, 'special_') || str_starts_with($key, 'func_')) {
            if (!is_callable($field['name'])) {
                return 'Função '.$field['name'].' não encontrada.';
            }
            return call_user_func($field['name'], $field, '', 'header');
        }
        if (str_starts_with($key, 'select_')) {
            if ($field['label']) {
                return $field['label'];
            }
            return $field['name'] instanceof TypeInterface ? $field['name']->getName() : 'Tipos';
        }
        return $field['name'];
    }
}
