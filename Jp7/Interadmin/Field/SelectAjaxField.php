<?php

namespace Jp7\Interadmin\Field;

class SelectAjaxField extends SelectField
{
    use SelectAjaxFieldTrait;

    protected function getFormerField()
    {
        return parent::getFormerField()
                ->data_ajax()
                ->data_id_tipo($this->type->id_tipo);
    }

    protected function getOptions()
    {
        return $this->toOptions($this->getCurrentRecords());
    }
}
