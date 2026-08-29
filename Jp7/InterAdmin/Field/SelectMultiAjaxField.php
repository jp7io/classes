<?php

namespace Jp7\InterAdmin\Field;

use Former;
use UnexpectedValueException;

class SelectMultiAjaxField extends SelectMultiField
{
    use SelectAjaxFieldTrait;

    protected function getFormerField()
    {
        $options = $this->getOptions();

        return Former::select($this->getFormerName().'[]') // multiple requires []
            ->id($this->getFormerId())
            ->options($options)
            // The ONLY thing that marks these as selected. Former 5.2 added a clearSelected()
            // at the top of Select::render() (5.1 had none), which strips the `selected`
            // attribute off every <option> and then re-adds it from the field's own value --
            // so setting it in the options array, as toMultipleOptions() used to, is now a
            // no-op. Every attached record rendered as an *unselected* <option> and select2
            // showed nothing but its "Procurar..." placeholder, on a field the listing page
            // showed as filled.
            //
            // ->value() defers to POST/old input when there is any (Former::getPost turns
            // "campo[]" back into "campo"), so a failed validation still repopulates.
            ->value(array_keys($options))
            ->multiple()
            ->data_ajax()
            ->data_id_tipo($this->type->id_tipo);
    }

    /**
     * Only the currently selected records -- every other option comes from the AJAX search.
     */
    protected function getOptions()
    {
        return $this->toOptions($this->getCurrentRecords());
    }

    public function getFilterTag()
    {
        $selectField = new SelectAjaxField($this->campo);
        $selectField->setRecord($this->record);
        $selectField->setType($this->type);
        return $selectField->getFilterTag();
    }
}
