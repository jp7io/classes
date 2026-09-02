<?php

namespace Jp7\InterAdmin\Field;

use Former;

/**
 * A char(1) flag storing 'S' or ''. After 2026_09_03_000000 no InterAdmin COLUMN is one: the
 * record slots, `publish`, `deleted` and the `types` flags are all tinyint, and {@see BoolField}
 * is what `field_type => 'bool'` resolves to.
 *
 * ⚠ It survives for values that are not columns. InterMail renders a template VARIABLE of type
 * Checkbox with it, and that value lives in a blob whose encoding is still 'S'. Reachable only by
 * an explicit `new`; nothing the Factory derives from a column name lands here any more.
 */
class CharField extends ColumnField
{
    protected $id = 'char';

    const XTRA_UNCHECKED = '0';
    const XTRA_CHECKED = 'S';

    /** The value a checked box posts, i.e. this column's spelling of true. */
    protected const CHECKED_VALUE = 'S';

    public function getCellHtml(): string
    {
        return $this->getValue() ? '&bull;' : '';
    }

    protected function getFormerField()
    {
        $input = Former::checkbox($this->getFormerName())
            ->id($this->getFormerId())
            ->setAttribute('value', static::CHECKED_VALUE)
            ->text('&nbsp;'); // Bootstrap CSS - padding
        // initial check status
        if ($input->getValue() === null && $this->getValue()) {
            $input->check();
        }
        return $input;
    }

    protected function getDefaultValue()
    {
        if ($this->default) {
            return $this->default;
        }
        if ($this->xtra === self::XTRA_CHECKED) {
            return static::CHECKED_VALUE;
        }
    }

    protected function handleReadonly($input)
    {
        // Former doesnt disable the hidden input
        if ($this->isReadonly()) {
            $input->push(false)->disabled();
        }
    }

    public function hasMassEdit(): bool
    {
        return true;
    }
}
