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

    /** How many related records a list cell shows before it starts hiding them. */
    private const CELL_LIMIT = 5;

    /**
     * The rest are behind a toggle, and the toggle targets this cell's own id.
     *
     * The class selector this used to carry would have toggled every row on the page at once --
     * "would have", because the markup was still Bootstrap 3 (`data-toggle`, `.collapse.in`) and
     * nothing in the app read it, so the sixth related record onwards was simply unreachable.
     */
    public function getCellHtml()
    {
        $textArray = $this->getTextArray(true);
        $visible = array_slice($textArray, 0, self::CELL_LIMIT);
        $hidden = array_slice($textArray, self::CELL_LIMIT);

        if (!$hidden) {
            return implode('<br>', $visible);
        }
        $id = 'select-multi-'.$this->tipo.'-'.(int) ($this->record->id ?? 0);

        return implode('<br>', $visible).
            '<div class="collapse" id="'.$id.'">'.implode('<br>', $hidden).'</div>'.
            '<button type="button" class="btn btn-link btn-sm p-0 collapsed" data-bs-toggle="collapse"'.
                ' data-bs-target="#'.$id.'" aria-expanded="false" aria-controls="'.$id.'">'.
                '+'.count($hidden).
            '</button>';
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

    public function hasTipo(): bool
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
            // Former keys a checkbox by its LABEL, so two records that read the same would
            // collapse into one box. The id rides along hidden to keep the key unique --
            // .option-key is display:none in pages/record-create-edit.scss.
            $checkboxes[$value.'<span class="option-key">'.$key.'</span>'] = [
                'value' => $key, // ID
                'checked' => in_array($key, $ids),
                'required' => false // HTML5 validation can't handle multiple checkboxes
            ];
        }
        return $checkboxes;
    }
}
