<?php

namespace Jp7\Interadmin\Field;

use Former;
use UnexpectedValueException;

class SelectMultiAjaxField extends SelectMultiField
{
    use SelectAjaxFieldTrait;

    protected function getFormerField()
    {
        return Former::select($this->getFormerName().'[]') // multiple requires []
            ->id($this->getFormerId())
            ->options($this->getOptions())
            ->multiple()
            ->data_ajax()
            ->data_id_tipo($this->type->id_tipo);
    }

    protected function getOptions()
    {
        return $this->toMultipleOptions($this->getCurrentRecords());
    }

    public function getFilterTag()
    {
        $selectField = new SelectAjaxField($this->campo);
        $selectField->setRecord($this->record);
        $selectField->setType($this->type);
        return $selectField->getFilterTag();
    }

    // We have more than one option selected, so we need to add the selected attribute to options
    protected function toMultipleOptions($array)
    {
        $options = [];
        foreach (parent::toOptions($array) as $id => $text) {
            $options[$text] = [
                'value' => $id,
                'selected' => true // We are assuming only selected records were found
            ];
        }
        return $options;
    }
}
