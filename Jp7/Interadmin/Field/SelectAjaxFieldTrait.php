<?php

namespace Jp7\Interadmin\Field;

use UnexpectedValueException;
use App\Models\Type;

trait SelectAjaxFieldTrait
{
    public function searchOptions($search)
    {
        if (!$this->hasTipo()) {
            $query = $this->buildSearch($this->records(false), $this->getSearchableFields(), $search);
            return $this->toJsonOptions($query->get());
        }
        if ($this->nome instanceof Type || $this->nome === 'all') {
            $query = $this->buildSearch($this->tipos(), ['nome'], $search);
            return $this->toJsonOptions($query->get());
        }
        throw new UnexpectedValueException('Not implemented');
    }

    protected function buildSearch($query, $fields, $search)
    {
        global $db;
        $pattern = '%' . str_replace(' ', '%', $search) . '%';
        $whereOr = [];
        foreach ($fields as $field) {
            $whereOr[] = $field . ' LIKE ' . $db->qstr($pattern);
        }
        if (is_numeric($search)) {
            $whereOr[] = 'type_id = ' . intval($search);
        }

        $order = [];
        foreach ($fields as $field) {
            $order[] = $field . ' LIKE ' . $db->qstr($search . '%') . ' DESC'; // starts with
        }
        $order = array_merge($order, $fields);

        return $query->whereRaw('(' . implode(' OR ', $whereOr) . ')')
            ->orderByRaw(implode(', ', $order))
            ->limit(100);
    }

    protected function getSearchableFields()
    {
        $campos = $this->nome->getFields();
        $searchable = [];

        foreach ($this->nome->getFieldsCombo() as $campoCombo) {
            if ($campos[$campoCombo]['nome'] instanceof Type) {
                foreach ($campos[$campoCombo]['nome']->getFieldsCombo() as $campoCombo2) {
                    $searchable[] = $campoCombo . '.' . $campoCombo2;
                }
            } else {
                $searchable[] = $campoCombo;
            }
        }
        return $searchable;
    }

    protected function toJsonOptions($array)
    {
        $options = [];
        foreach ($this->toOptions($array) as $id => $text) {
            $options[] = [
                'id' => $id,
                'text' => $text
            ];
        }
        return $options;
    }
}
