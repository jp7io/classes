<?php

namespace Jp7\InterAdmin\Field;

class FieldGroup
{
    /**
     * @var FieldInterface[]
     */
    protected $fields;

    public function add(FieldInterface $field): void
    {
        $this->fields[] = $field;
    }

    public function getEditTag(): string
    {
        $html = '';
        $first = $this->fields[0];
        if ($first instanceof TitField) {
            $html .= $first->openPanel();
        } else {
            $firstClass = (isset($first->nome_id) ? $first->nome_id.'-panel' : '');
            $html .= '<div class="card card-default '.$firstClass.'">'.
                        '<div class="card-body">';
        }

        $html .= implode(PHP_EOL, array_map(function (FieldInterface $field): string {
            // (string) is needed to force render
            // Former wants object to be created and rendered before creating next object
            // Without (string) the group doesn't get the class "required"
            return ($field instanceof TitField) ? '' : (string) $field->getEditTag();
        }, $this->fields));

        if ($first instanceof TitField) {
            $html .= $first->closePanel();
        } else {
            $html .= '</div>'.
                        '</div>';
        }

        return $html;
    }
}
