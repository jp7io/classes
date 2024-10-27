<?php

namespace Jp7\Interadmin\Field;

class SelectAjaxField extends SelectField
{
    use SelectAjaxFieldTrait;

    protected function getFormerField()
    {
        return parent::getFormerField()
                ->data_ajax()
                ->data_type_id(is_object($this->type) ? $this->type->type_id : 0);
    }

    protected function getOptions()
    {
        return $this->toOptions($this->getCurrentRecords());
    }
}
