<?php

namespace Jp7\InterAdmin\Field;

use Former;
use HtmlObject\Element;

class SelectField extends ColumnField
{
    use SelectFieldTrait;

    protected $id = 'select';

    const XTRA_RECORD = '';
    const XTRA_RECORD_RADIO = 'records_radio';
    const XTRA_RECORD_AJAX = 'records_ajax';
    const XTRA_TYPE = 'types';
    const XTRA_TYPE_RADIO = 'types_radio';
    const XTRA_TYPE_AJAX = 'types_ajax';

    public function getCellHtml(): string
    {
        return $this->formatTextRelated(true);
    }

    public function getText(): string
    {
        return $this->formatTextRelated(false);
    }

    protected function formatTextRelated($html): string
    {
        $currentRecords = $this->getCurrentRecords();
        if (count($currentRecords)) {
            $related = $currentRecords[0];
        } else {
            $related = $this->getValue(); // to show only an ID
        }
        return $this->formatText($related, $html);
    }

    public function hasType(): bool
    {
        return in_array($this->xtra, [self::XTRA_TYPE, self::XTRA_TYPE_AJAX, self::XTRA_TYPE_RADIO]);
    }

    public function hasMassEdit(): bool
    {
        return true;
    }

    protected function getFormerField()
    {
        return Former::select($this->getFormerName())
            ->id($this->getFormerId())
            ->value($this->getValue())
            ->options($this->getOptions());
    }

    protected function getFilterField()
    {
        return $this->getFormerField();
    }

    public function getFilterTag()
    {
        $this->filterCombo = true;
        $field = $this->getFilterField();
        $blank = Element::create('option', '(vazio)')->setAttribute('value', 'blank');
        $field->prependChild($blank, $blank->getAttribute('value'));
        
        return $field->name('filter_'.$this->getFormerName())
            ->removeClass('form-control')
            ->addClass('filter-select')
            ->data_allow_blank()
            ->data_field($this->getFormerName())
            ->raw();
    }
}
