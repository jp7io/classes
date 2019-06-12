<?php

namespace Jp7\Former\Fields;

class Checkbox extends \Former\Form\Fields\Checkbox
{
    // Temp fix, see: https://github.com/formers/former/pull/584
    public function render()
	{
        $this->value = e($this->value);
        return parent::render();
    }
}
