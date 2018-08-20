<?php

namespace Jp7\Interadmin\Field;

use Former;

class SelectMultiField extends ColumnField
{
    use SelectFieldTrait;

    protected $id = 'select_multi';

    const XTRA_RECORD = '0'; // checkboxes
    const XTRA_TYPE = 'S';   // checkboxes
    const XTRA_RECORD_SEARCH = 'X';
    const XTRA_TYPE_SEARCH = 'X_tipos';

    public function getCellHtml()
    {
        $textArray = $this->getTextArray(true);
        $visibleArray = array_slice($textArray, 0, 5);
        $expandableArray = array_slice($textArray, 5);
        $id = 'multi'.uniqid();

        return $expandableArray ?
            '<div data-toggle="collapse" data-target=".'.$id.'">'.
                implode('<br>', $visibleArray).
                '<br>'.
                '<div class="'.$id.' collapse in">...</div>'.
                '</div>'.
                '<div class="'.$id.' collapse">'.implode('<br>', $expandableArray).'</div>' :
            implode('<br>', $visibleArray);
    }

    public function getText()
    {
        return implode(",\n", $this->getTextArray(false));
    }

    protected function getTextArray($html)
    {
        $array = [];
        foreach ($this->getCurrentRecords() as $related) {
            $array[] = $this->formatText($related, $html);
        }
        return $array;
    }

    public function hasTipo()
    {
        return in_array($this->xtra, [self::XTRA_TYPE, self::XTRA_TYPE_SEARCH]);
    }

    public function getEditTag()
    {
        return $this->getPushInput().parent::getEditTag();
    }

    protected function getPushInput()
    {
        // Push checkbox / Former can't handle this on multiple checkboxes
        if ($this->isReadonly()) {
            return '';
        }
        return '<input type="hidden" value="" name="'.$this->getFormerName().'" />';
    }

    protected function getFormerField()
    {
        $field = Former::checkboxes($this->getFormerName().'[]'); // [] makes it "grouped"
        $checkboxes = $this->getCheckboxes($field);
        if (!$checkboxes) {
            // BUG: empty options render a ghost checkbox
            return Former::text($this->getFormerName().'[]')->readonly();
        }
        return $field->push(false)
                ->checkboxes($checkboxes)
                ->onGroupAddClass('has-checkboxes');
                // ->id($this->getFormerId()) // Wont work with checkboxes
    }

    public function getFilterTag()
    {
        $selectField = new SelectField($this->campo);
        $selectField->setRecord($this->record);
        $selectField->setType($this->type);
        return $selectField->getFilterTag();
    }

    protected function getCheckboxes($field)
    {
        $checkboxes = [];
        // Problem with populate from POST: https://github.com/formers/former/issues/364
        $ids = $field->getValue();
        if (!$ids) {
            $ids = array_filter(explode(',', $this->getValue()));
        }

        foreach ($this->getOptions() as $key => $value) {
            $checkboxes[$value.'<s>'.$key.'</s>'] = [ // s = avoid collision
                'value' => $key, // ID
                'checked' => in_array($key, $ids),
                'required' => false // HTML5 validation can't handle multiple checkboxes
            ];
        }
        return $checkboxes;
    }
}
