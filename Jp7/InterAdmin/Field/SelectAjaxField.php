<?php

namespace Jp7\InterAdmin\Field;

class SelectAjaxField extends SelectField
{
    use SelectAjaxFieldTrait;

    protected function getFormerField()
    {
        return parent::getFormerField()
                ->data_ajax()
                ->data_type_id($this->type->type_id);
    }

    protected function getOptions()
    {
        return $this->toOptions($this->getCurrentRecords());
    }
}
