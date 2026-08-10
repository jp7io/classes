<?php

namespace Jp7\Interadmin\Field;

use Former;
use Former\Form\Fields\Checkbox;
use HtmlObject\Element;
use HtmlObject\Input;

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

    /** What the card renders, kept from getFormerField() so its header can count the options. */
    private $checkboxes = [];

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
        $input = $this->getFormerField();
        if (!$input instanceof Checkbox) {
            // A search variant is a select2, and a field with no options falls back to a text box.
            return $this->getPushInput().$this->applyGroupSettings($input->label($this->getLabel()));
        }
        $this->handleReadonly($input);

        return $this->getPushInput().$this->getRowHtml($input);
    }

    /**
     * The options are a card with a sticky "Todos" header, and both are rendered here rather than
     * by partials/init-checkboxes.js: built in JS they arrived a frame after first paint, so every
     * panel below the field dropped by the header's height as the page finished loading.
     */
    protected function getRowHtml($input): string
    {
        $card = Element::div($this->getSelectAllHtml().$input->render())
            ->class('field-help-body')
            ->setAttribute('role', 'group')
            ->setAttribute('aria-labelledby', $this->getLabelId());
        // Where Former puts it on every other field: in the column, in a plain div, after the
        // control -- partials/field-help.js reads that shape to build the (?) button.
        $help = $this->ajuda ? Element::div(Element::span($this->ajuda)->class('form-text')) : '';
        $column = Element::div($card.$help)->class('col-lg-10 col-sm-8');

        return Element::div($this->getLabelHtml().$column)
            ->class(implode(' ', $this->getRowClasses($input)));
    }

    /**
     * Former marks a required group from the rules the form was opened with, so a row built by
     * hand has to ask the field whether those rules reached it.
     */
    protected function getRowClasses($input): array
    {
        $classes = array_merge(['mb-3', 'row', 'has-checkboxes'], $this->getGroupClasses());
        if ($input->isRequired()) {
            $classes[] = 'required';
        }
        return $classes;
    }

    /**
     * A span rather than a <label>, because a checkbox list has no one control to label: Former
     * ids each box `<name>[]`, `<name>[]2`, ... and the browser reports a <label> that reaches
     * none of them -- with or without a `for` -- as a form field with no label at all. The card
     * is a named `role="group"` instead, which is what carries the name to a screen reader.
     * The classes are the ones Former's own label would carry; see form.scss for the two rules
     * that follow them.
     */
    protected function getLabelHtml()
    {
        return Element::span($this->getLabel())
            ->class('col-form-label col-lg-2 col-sm-4 pt-0')
            ->setAttribute('id', $this->getLabelId())
            ->setAttribute('title', $this->getLabelTitle());
    }

    private function getLabelId(): string
    {
        return $this->getFormerId().'-label';
    }

    /**
     * "Todos" plus the count. Nameless, so it never posts -- partials/init-checkboxes.js wires it
     * to the options below and keeps the count in step.
     */
    protected function getSelectAllHtml(): string
    {
        $id = 'select-all-'.$this->getFormerId();
        $checked = count(array_filter(array_column($this->checkboxes, 'checked')));

        $box = Input::create('checkbox', null, null, [
            'class' => 'form-check-input',
            'id' => $id,
            'checked' => $this->checkboxes && $checked === count($this->checkboxes),
            'disabled' => (bool) $this->isReadonly(),
        ]);
        $label = Element::label('Todos')->class('form-check-label')->setAttribute('for', $id);

        return (string) Element::div(
            Element::div($box.$label)->class('form-check').
            Element::span($checked.' selecionado(s)')
        )->class('select-all');
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
        $this->checkboxes = $checkboxes = $this->getCheckboxes($field);
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
            $ids = array_filter(explode(',', (string) $this->getValue()));
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
