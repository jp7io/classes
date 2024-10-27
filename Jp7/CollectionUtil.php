<?php

namespace Jp7;

use Jp7\Interadmin\Relation;

/**
 * Class for handling collections of objects.
 * @deprecated
 */
class CollectionUtil
{
    /**
     * Keys are strings.
     *
     * @param array  $array
     * @param string $clause
     *
     * @return array
     */
    public static function separate($array, $clause)
    {
        $separated = [];

        $properties = explode('.', $clause);
        foreach ($array as $item) {
            $key = $item;
            foreach ($properties as $property) {
                $key = @$key->$property;
            }
            $separated[$key][] = $item;
        }

        return $separated;
    }

    public static function getFieldsValues($array, $fields, $fields_alias)
    {
        if (count($array) > 0) {
            $first = reset($array);

            $type = $first->getType();
            $retornos = $type->find([
                'class' => 'Jp7\\Interadmin\\Record',
                'fields' => $fields,
                'fields_alias' => $fields_alias,
                'where' => ['id IN ('.implode(',', $array).')'],
                'order' => 'FIELD(id,'.implode(',', $array).')',
                //'debug' => true
            ]);
            foreach ($retornos as $key => $retorno) {
                $array[$key]->attributes = $retorno->attributes + $array[$key]->attributes;
            }
        }
    }

    /**
     * @deprecated
     */
    public static function eagerLoad($records, $relationships, $selectStack = null)
    {
        return Relation::eagerLoad($records, $relationships, $selectStack);
    }
}
