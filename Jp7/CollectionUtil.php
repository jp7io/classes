<?php

namespace Jp7;

use Jp7\Interadmin\Type;

/**
 * Class for handling collections of objects.
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

            $tipo = $first->getType();
            $retornos = $tipo->find([
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

    public static function eagerLoad($records, $relationships)
    {
        if (!is_array($records)) {
            $records = $records->all();
        }
        if (!$records) {
            return;
        }
        if (!is_array($relationships)) {
            $relationships = [$relationships];
        }
        $relation = array_shift($relationships);
        $model = reset($records);
        if ($data = $model->getType()->getRelationshipData($relation)) {
            if ($data['type'] == 'select') {
                if ($data['multi']) {
                    self::eagerLoadSelectMulti($records, $relationships, $relation, $data);
                } else {
                    self::eagerLoadSelect($records, $relationships, $relation, $data);
                }
            } elseif ($data['type'] == 'children') {
                self::eagerLoadChildren($records, $relationships, $relation, $data);
            } else {
                throw new \Exception('Unsupported relationship type: "'.$data['type'].'" for class '.get_class($model).' - ID: '.$model->id);
            }
        } else {
            throw new \Exception('Unknown relationship: "'.$relation.'" for class '.get_class($model).' - ID: '.$model->id);
        }
    }

    protected static function eagerLoadSelectMulti($records, $relationships, $relation, $data)
    {
        if (reset($records)->hasLoadedRelation($relation)) {
            if ($relationships) {
                $rows = collect(array_column($records, $relation))->flatten();
                self::eagerLoad($rows, $relationships);
            }
            return;
        }

        // select_multi.id IN (record.select_multi_ids)
        $ids = [];
        $alias = $relation.'_ids';
        foreach ($records as $record) {
            $fks = $record->$alias;
            $fksArray = is_array($fks) ? $fks : array_filter(explode(',', $fks));
            $ids = array_merge($ids, $fksArray);
        }
        $ids = array_unique($ids);

        if (!$ids) {
            return;
        }

        if ($data['has_type']) {
            $rows = jp7_collect([]);
            foreach ($ids as $id) {
                $rows[$id] = Type::getInstance($id);
            }
        } else {
            $rows = $data['tipo']
                ->records()
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id');
        }

        foreach ($records as $record) {
            $loaded = (object) [
                'fks' => $record->$alias,
                'values' => jp7_collect([])
            ];
            $fksArray = is_array($loaded->fks) ? $loaded->fks : array_filter(explode(',', $loaded->fks));
            foreach ($fksArray as $fk) {
                if (isset($rows[$fk])) {
                    $loaded->values[] = $rows[$fk];
                }
            }
            $record->setRelation($relation, $loaded);
        }
    }

    protected static function eagerLoadSelect($records, $relationships, $relation, $data)
    {

        if (reset($records)->hasLoadedRelation($relation)) {
            if ($relationships) {
                $rows = array_filter(array_column($records, $relation));
                self::eagerLoad($rows, $relationships);
            }
            return;
        }

        // select.id = record.select_id
        $alias = $relation.'_id';
        $ids = array_filter(array_unique(array_column($records, $alias)));

        if ($data['has_type']) {
            $rows = jp7_collect([]);
            foreach ($ids as $id) {
                $rows[$id] = Type::getInstance($id);
            }
        } else {
            $rows = $data['tipo']
                ->records()
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id');
        }
        if ($relationships) {
            self::eagerLoad($rows, $relationships);
        }
        foreach ($records as $record) {
            $id = $record->$alias;
            $record->setRelation($relation, $rows[$id] ?? null);
        }
    }

    protected static function eagerLoadChildren($records, $relationships, $relation, $data)
    {
        if (reset($records)->hasLoadedRelation($relation)) {
            if ($relationships) {
                $rows = collect(array_column($records, $relation))->flatten();
                self::eagerLoad($rows, $relationships);
            }
            return;
        }

        // child.parent_id = parent.id
        $data['tipo']->setParent(null);
        $children = $data['tipo']
            ->records()
            ->whereIn('parent_id', $records)
            ->get();
        if ($relationships) {
            self::eagerLoad($children, $relationships);
        }
        $children = $children->groupBy('parent_id');

        foreach ($records as $record) {
            $record->setRelation($relation, $children[$record->id] ?? jp7_collect());
        }
    }
}
