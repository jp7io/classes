<?php

namespace Jp7\Interadmin\Field;

class FloatField extends ColumnField
{
    protected $id = 'float';

    public function getRules()
    {
        $rules = parent::getRules();
        $rules[$this->getRuleName()][] = 'numeric';
        return $rules;
    }

    /**
     * Re-emit Former's `numeric` pattern in a form browsers can still compile.
     *
     * Former\LiveValidation::numeric() turns the rule above into
     * pattern="[+-]?\d*\.?\d+". Browsers now build the pattern attribute's regex with the
     * `v` flag, which reserves `-` as a class-set syntax character, so a bare `-` inside
     * [...] is a hard SyntaxError ("Invalid character in character class") -- logged to the
     * console on every record form with an int/float field, and the whole constraint is
     * dropped. Same expression, with the `-` escaped so it compiles.
     *
     * Live validation runs in the Former field's constructor, so setting the attribute here
     * (after parent::getFormerField() built the field) overwrites Former's version.
     */
    protected function getFormerField()
    {
        return parent::getFormerField()->pattern('[+\-]?\d*\.?\d+');
    }

    public function hasMassEdit()
    {
        return true;
    }
}
