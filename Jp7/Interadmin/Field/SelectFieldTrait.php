<?php

namespace Jp7\Interadmin\Field;

use Jp7\Interadmin\Record;
use Jp7\Interadmin\Type;
use Jp7\Interadmin\Query\TypeQuery;
use UnexpectedValueException;
use Cache;

trait SelectFieldTrait
{
    protected $filterCombo = false;

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

    protected function formatText($related, $html)
    {
        list($value, $status) = $this->valueAndStatus($related);
        if ($html) {
            return ($status ? e($value) : '<del>'.e($value).'</del>');
        }
        return $value.($status ? '' : ' [unpublished]');
    }

    protected function valueAndStatus($related)
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
                $selectedObj = $this->nome->findByIdString($idString);
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
     * @throws Exception
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
            //return $this->tipos()->whereIn('id_tipo', $ids)->get();
            $cached = new \Jp7\Interadmin\Collection();
            foreach ($ids as $id_tipo) {
                $type = Type::getInstance($id_tipo);
                if ($type->nome !== null) { // deleted types
                    $cached[] = $type;
                }
            }
            return $cached;
        }
        throw new UnexpectedValueException('Not implemented');
    }

    protected function cachedRecords($ids)
    {
        $prefix = 'cachedRecords,'.$this->nome->id_tipo;
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
                Cache::put($prefix.','.$id, $found ? $record->getAttributes() : false, 10);
            }
        }
        return new \Jp7\Interadmin\Collection(array_values(array_filter($cached)));
    }

    protected function getOptions()
    {
        if (!$this->hasTipo()) {
            $cacheKey = 'cachedOptions,'.$this->nome->id_tipo;
            $resolve = function () {
                return $this->toOptions($this->records()->get());
            };
            if ($this->filterCombo) {
                return Cache::remember($cacheKey, 10, $resolve);
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
        $camposCombo = $this->nome->getCamposCombo();
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

    protected function tipos()
    {
        global $lang;

        $query = new TypeQuery;
        $query->select('nome'.$lang->prefix, 'parent_id_tipo')
            ->published()
            ->orderByRaw('admin,ordem,nome'.$lang->prefix);
        // only children tipos
        if ($this->nome instanceof Type) {
            $query->where('parent_id_tipo', $this->nome->id_tipo);
        }
        return $query;
    }

    protected function toOptions($array)
    {
        $options = [];
        if (!empty($array[0]) && $array[0] instanceof Type) {
            foreach ($array as $tipo) {
                $options[$tipo->id_tipo] = e($tipo->getName());
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

    protected function toTreeOptions($tipos)
    {
        $map = [];
        foreach ($tipos as $tipo) {
            $map[$tipo->parent_id_tipo][] = $tipo;
        }
        $options = [];
        $this->addTipoTreeOptions($options, $map, 0);
        return $options;
    }

    protected function addTipoTreeOptions(&$options, $map, $parent_id_tipo, $level = 0)
    {
        if (!empty($map[$parent_id_tipo])) {
            foreach ($map[$parent_id_tipo] as $tipo) {
                $prefix = ($level ? str_repeat('--', $level) . '> ' : ''); // ----> Nome
                $options[$tipo->id_tipo] = $prefix.$tipo->getName();
                $this->addTipoTreeOptions($options, $map, $tipo->id_tipo, $level + 1);
            }
        }
    }
}
