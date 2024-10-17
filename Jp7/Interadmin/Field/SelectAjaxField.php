<?php

namespace Jp7\Interadmin\Field;

class SelectAjaxField extends SelectField
{
    use SelectAjaxFieldTrait;

    protected function getFormerField()
    {
        return parent::getFormerField()
                ->data_ajax()
                ->data_id_tipo(is_object($this->type) ? $this->type->id_tipo : 0);
    }

    protected function getOptions()
    {
        return $this->toOptions($this->getCurrentRecords());
    }
}
