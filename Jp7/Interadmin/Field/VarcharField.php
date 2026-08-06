<?php

namespace Jp7\Interadmin\Field;

class VarcharField extends ColumnField
{
    protected $id = 'varchar';

    public function getRules()
    {
        $rules = parent::getRules();
        $name = $this->getRuleName();
        $xtra = $this->getXtra();

        if ($xtra?->isUnique()) {
            $rules[$name][] = $this->record->getUniqueRule($this->tipo);
        }
        if ($rule = $xtra?->rule()) {
            $rules[$name][] = $rule;
        }
        if ($this->tamanho) {
            $rules[$name][] = 'max:'.$this->tamanho;
        }
        return $rules;
    }

    /**
     * null for the plain "Normal" xtra, and for any value the field editor no longer offers --
     * both mean "a varchar with no format", which is what an unrecognised one has always been.
     */
    protected function getXtra(): ?VarcharXtra
    {
        return VarcharXtra::tryFrom((string) $this->xtra);
    }

    protected function getFormerField()
    {
        $input = parent::getFormerField();
        $xtra = $this->getXtra();

        if ($type = $xtra?->inputType()) {
            $input->type($type);
        }
        if ($pattern = $xtra?->pattern()) {
            $input->pattern($pattern);
        }
        if ($xtra === VarcharXtra::Color) {
            $input->prepend($this->getColorpickerHtml());
        }
        return $input->data_type($this->xtra ?: false);
    }

    public function hasMassEdit()
    {
        return true;
    }

    /**
     * Former's Bootstrap 5 framework passes a prepended item through untouched only when its
     * markup starts with "<button", and wraps anything else in an .input-group-text -- which put
     * a block <div> inside a <span> and buried the swatch in a grey addon. It is a button in
     * substance too: partials/colorpicker.js hangs the picker off it.
     */
    protected function getColorpickerHtml()
    {
        return '<button type="button" class="btn btn-outline-secondary colorpicker-button"'.
            ' aria-label="Escolher cor"></button>';
    }
}
