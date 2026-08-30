<?php

namespace Jp7\InterAdmin\Field;

use Former;

class SelectRadioField extends SelectField
{
    protected function getFormerField()
    {
        return Former::radios($this->getFormerName())
                // ->id($this->getFormerId()) // TODO test this
                ->radios($this->getRadios())
                ->check($this->getValue());
    }

    protected function getRadios(): array
    {
        $radios = [];
        if (!$this->required) {
            $radios['(nenhum)'] = ['value' => '', 'checked' => true];
        }
        foreach ($this->getOptions() as $key => $value) {
            $radios[$value] = ['value' => $key];
        }
        return $radios;
    }

    protected function getFilterField()
    {
        return parent::getFormerField();
    }
}
