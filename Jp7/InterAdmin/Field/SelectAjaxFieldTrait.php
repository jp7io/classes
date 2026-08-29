<?php

namespace Jp7\InterAdmin\Field;

use UnexpectedValueException;
use Jp7\InterAdmin\Type;

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

    /**
     * Match the search against every field the combo shows, best prefix match first.
     *
     * The WHERE goes through the query builder, which escapes the term itself. ORDER BY still has
     * to be raw -- ranking by `field LIKE 'term%'` is an expression, not a comparison the builder
     * models -- so that one term is quoted through the connection's own PDO.
     *
     * @param string[] $fields Column names, or `relation.column` paths from getSearchableFields().
     */
    protected function buildSearch($query, array $fields, string $search)
    {
        $pattern = '%'.str_replace(' ', '%', $search).'%';

        $query->where(function ($group) use ($fields, $pattern, $search): void {
            foreach ($fields as $field) {
                $group->orWhere($field, 'like', $pattern);
            }
            if (is_numeric($search)) {
                $group->orWhere('id_tipo', (int) $search);
            }
        });

        $startsWith = \DB::connection()->getPdo()->quote($search.'%');
        $order = array_map(fn (string $field): string => $field.' LIKE '.$startsWith.' DESC', $fields);

        return $query->orderByRaw(implode(', ', array_merge($order, $fields)))
            ->limit(100);
    }

    protected function getSearchableFields(): array
    {
        $campos = $this->nome->getFields();
        $searchable = [];

        foreach ($this->nome->getComboFieldNames() as $comboColumn) {
            if ($campos[$comboColumn]['nome'] instanceof Type) {
                foreach ($campos[$comboColumn]['nome']->getComboFieldNames() as $subComboColumn) {
                    $searchable[] = $comboColumn.'.'.$subComboColumn;
                }
            } else {
                $searchable[] = $comboColumn;
            }
        }
        return $searchable;
    }

    protected function toJsonOptions($array): array
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
