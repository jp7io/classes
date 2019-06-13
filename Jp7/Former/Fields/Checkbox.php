<?php

namespace Jp7\Former\Fields;

class Checkbox extends \Former\Form\Fields\Checkbox
{
    // Temp fix, see: https://github.com/formers/former/pull/584
    public function render()
	{
        $this->value = is_string($this->value) ? e($this->value) : $this->value;
        foreach ($this->items as $key => $item) {
            if (is_array($this->value) && isset($item['label'])) {
                $this->items[$key]['label'] = e($item['label']);
            }
        }
        return parent::render();
    }
}
