<?php

namespace Jp7\InterAdmin\Field;

use Jp7\InterAdmin\Record;
use Jp7\InterAdmin\Type;
use Jp7\InterAdmin\Query\TypeQuery;
use UnexpectedValueException;
use Cache;
use Lang;

/**
 * Everything a select-shaped field does with the type or the records on the other end.
 *
 * Besides hasTipo() below, this reads four values off the `campo` blob through ColumnField's
 * __get -- `nome` (a Type, a type id, or the literal 'all'), `where`, `default` and `label` --
 * which a trait cannot declare. A user of this trait is a ColumnField.
 */
trait SelectFieldTrait
{
    protected $filterCombo = false;

    /**
     * Whether the options are TYPES rather than records of one type. Half the methods here fork
     * on it, so it is declared rather than left to fail at render time on a class that forgot.
     */
    abstract public function hasTipo(): bool;

    public function getLabel()
    {
        if ($this->label) {
            return $this->label;
        }
        if ($this->nome instanceof Type) {
            return $this->nome->getName();
        }
        if ($this->nome === 'all') {
            return 'Tipos';
        }
        throw new UnexpectedValueException('Not implemented');
    }

    protected function formatText($related, $html): string
    {
        list($value, $status) = $this->valueAndStatus($related);
        if ($html) {
            return ($status ? e($value) : '<del>'.e($value).'</del>');
        }
        return $value.($status ? '' : ' [unpublished]');
    }

    protected function valueAndStatus($related): array
    {
        if ($related instanceof Type) {
            return [$related->getName(), true];
        }
        if ($related instanceof Record) {
            return [$related->getStringValue(), $related->isPublished()];
        }
        if (!$related) {
            return ['', true];
        }
        return [$related, false];
    }

    protected function getDefaultValue()
    {
        if ($this->default && !is_numeric($this->default) && $this->nome instanceof Type) {
            $defaultArr = [];
            foreach (array_filter(explode(',', $this->default)) as $idString) {
                // records() takes no arguments, so an options array here is discarded in
                // silence and first() answers with whichever record comes first.
                $selectedObj = $this->nome->records()->where('id_string', $idString)->first();
                if ($selectedObj) {
                    $defaultArr[] = $selectedObj->id;
                }
            }
            if ($defaultArr) {
                $this->default = implode(',', $defaultArr);
            }
        }
        return $this->default;
    }

    /**
     * Returns only the current selected option, all the other options will be
     * provided by the AJAX search
     * @return array
     * @throws \UnexpectedValueException
     */
    protected function getCurrentRecords()
    {
        $ids = explode(',', $this->getValue());
        $ids = array_values(array_filter(array_filter($ids), 'is_numeric'));
        $old = old($this->tipo);
        if ($old) {
            // previous POST values needs to be available for Former to select it
            $ids = array_unique(array_merge($ids, $old));
        }
        if (!$ids) {
            return []; // evita query inutil
        }
        if (!$this->hasTipo()) {
            //return $this->records()->whereIn('id', $ids)->get();
            return $this->cachedRecords($ids);
        }
        if ($this->nome instanceof Type || $this->nome === 'all') {
            //return $this->tipos()->whereIn('type_id', $ids)->get();
            $cached = new \Jp7\InterAdmin\Collection();
            foreach ($ids as $id_tipo) {
                $type = Type::getInstance($id_tipo);
                if ($type->name !== null) { // deleted types
                    $cached[] = $type;
                }
            }
            return $cached;
        }
        throw new UnexpectedValueException('Not implemented');
    }

    protected function cachedRecords($ids): \Jp7\InterAdmin\Collection
    {
        $prefix = 'cachedRecords,'.$this->nome->type_id;
        $cached = [];
        foreach ($ids as $key => $id) {
            $attributes = Cache::get($prefix.','.$id);
            if ($attributes === false) {
                // cached with empty value
                $cached[$key] = null;
            } elseif ($attributes) {
                // cached
                $cached[$key] = Record::getInstance($id, [], $this->nome);
                $cached[$key]->setRawAttributes($attributes);
            }
        }
        if ($pending = array_diff_key($ids, $cached)) {
            $records = $this->records()->findMany($pending);
            foreach ($pending as $key => $id) {
                $found = null;
                foreach ($records as $record) {
                    if ($record->id == $id) {
                        $found = $record;
                        break;
                    }
                }
                $cached[$key] = $found;
                // getAttributes: less serialized data
                Cache::put($prefix.','.$id, $found ? $found->getAttributes() : false, 600);
            }
        }
        return new \Jp7\InterAdmin\Collection(array_values(array_filter($cached)));
    }

    protected function getOptions()
    {
        if (!$this->hasTipo()) {
            $cacheKey = 'cachedOptions,'.$this->nome->type_id;
            $resolve = function () {
                return $this->toOptions($this->records()->get());
            };
            if ($this->filterCombo) {
                return Cache::remember($cacheKey, 600, $resolve);
            } else {
                return $resolve();
            }
        }
        if ($this->nome instanceof Type) {
            return $this->toOptions($this->tipos()->get());
        }
        if ($this->nome === 'all') {
            return $this->toTreeOptions($this->tipos()->get());
        }
        throw new UnexpectedValueException('Not implemented');
    }

    protected function records($ordered = true)
    {
        $camposCombo = $this->nome->getComboFieldNames();
        if (!$camposCombo) {
            $camposCombo = ['id'];
        }
        $query = $this->nome->records();
        // used later by isPublished()
        $camposPublished = ['char_key', 'parent_id', 'publish', 'deleted', 'date_publish', 'date_expire'];
        $query->select(array_merge($camposCombo, $camposPublished))
            ->where('deleted', false);
        if ($ordered) {
            $query->orderByRaw(implode(', ', $camposCombo));
        }
        if ($this->where) {
            // From xtra_disabledfields
            $query->whereRaw('1=1'.$this->where);
        }
        return $query;
    }

    protected function tipos(): TypeQuery
    {
        // The same translated-column suffix Type::getName() reads, instead of the `$lang` global
        // it used to reach for. Both resolve to the object Tenant::readClientEnv builds; this one
        // does not need it to still be in scope.
        $suffix = Lang::get('interadmin.suffix');

        $query = new TypeQuery;
        $query->select('name'.$suffix, 'parent_id_tipo')
            ->published()
            ->orderByRaw('admin,ordem,name'.$suffix);
        // only children tipos
        if ($this->nome instanceof Type) {
            $query->where('parent_id_tipo', $this->nome->type_id);
        }
        return $query;
    }

    /**
     * @return string[]
     */
    protected function toOptions($array)
    {
        $options = [];
        if (!empty($array[0]) && $array[0] instanceof Type) {
            foreach ($array as $tipo) {
                $options[$tipo->type_id] = e($tipo->getName());
            }
        } elseif (!empty($array[0]) && $array[0] instanceof Record) {
            foreach ($array as $record) {
                $options[$record->id] = e($record->getStringValue() . ($record->isPublished() ? '': ' (despublicado)'));
            }
        } elseif (count($array)) {
            throw new UnexpectedValueException('Should be an array of Record or Type');
        }
        // Append ID to duplicated values
        foreach (array_count_values($options) as $text => $count) {
            if ($count < 2) {
                continue;
            }
            for ($count; $count > 0; $count--) {
                $id = array_search($text, $options);
                $options[$id] = $text.' ('.$id.')';
            }
        }
        return $options;
    }

    protected function toTreeOptions($tipos): array
    {
        $map = [];
        foreach ($tipos as $tipo) {
            $map[$tipo->parent_id_tipo][] = $tipo;
        }
        $options = [];
        $this->addTipoTreeOptions($options, $map, 0);
        return $options;
    }

    protected function addTipoTreeOptions(array &$options, array $map, $parent_id_tipo, $level = 0)
    {
        if (!empty($map[$parent_id_tipo])) {
            foreach ($map[$parent_id_tipo] as $tipo) {
                $prefix = ($level ? str_repeat('--', $level) . '> ' : ''); // ----> Nome
                $options[$tipo->type_id] = $prefix.$tipo->getName();
                $this->addTipoTreeOptions($options, $map, $tipo->type_id, $level + 1);
            }
        }
    }
}
