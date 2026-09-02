<?php

namespace Jp7\InterAdmin\Field;

use Former;

/**
 * A char(1) flag storing 'S' or ''. What is left of that encoding after 2026_09_02_000000:
 * `publish` and `deleted` on every record table, and the `types` flags.
 *
 * ⚠ A record's own boolean slots are NOT these any more. They are `bool_*` tinyint(1), and
 * {@see BoolField} is the subclass that posts 1 instead of 'S'.
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
