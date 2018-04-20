<?php

namespace Jp7\Former\Fields;

use Closure;

class Select extends \Former\Form\Fields\Select
{
    private $lazyOptions;
    private $noPlaceholder = false;

    public function options($list, $selected = null, $valuesAsKeys = false)
    {
        if ($list instanceof \Jp7\Interadmin\Query\BaseQuery) {
            throw new \Exception('Use ->lists(attr_name,id)');
        }

        // clear previous options
        $this->children = [];
        $this->lazyOptions = null;

        // Lazy loading options
        if ($list instanceof Closure) {
            $this->lazyOptions = $list;
            return $this;
        }

        return parent::options($list, $selected, $valuesAsKeys);
    }

    public function placeholder($placeholder)
    {
        if ($placeholder === false) {
            $this->noPlaceholder = true;
        }
        return parent::placeholder($placeholder);
    }

    public function render()
    {
        // Lazy loading options
        if ($this->lazyOptions) {
            parent::options(call_user_func($this->lazyOptions));
        }
        // Use "Selecione" as default placeholder
        if ($this->getPlaceholder() === false && !$this->noPlaceholder) {
            if (empty($this->attributes['multiple'])) {
                $this->placeholder('Selecione');
            }
        }
        // Remove "disabled" from placeholder <option>
        $option = '<option value=""';
        return str_replace($option.' disabled="disabled"', $option, parent::render());
    }
}
