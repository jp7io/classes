<?php

namespace Jp7\InterAdmin\Field;

use Former;
use HtmlObject\Element;
use Carbon\Carbon as Date;

trait DateFieldTrait
{
    public function getText()
    {
        return $this->formatValue('d/m/Y'.($this->isDatetime() ? ' H:i' : ''));
    }

    protected function getValue()
    {
        return $this->formatValue('Y-m-d'.($this->isDatetime() ? '\TH:i' : ''));
    }

    protected function formatValue(string $format): string
    {
        $value = parent::getValue();
        if (!$value instanceof Date) {
            $value = new Date($value ?: '0000-00-00 00:00:00');
        }
        // Empty/zero date: Jp7_Date masked the year to "0000" while plain Carbon underflows
        // to "-0001"; both cast to < 1, so this is valid whether $value is a (legacy)
        // Jp7_Date instance or a plain Carbon.
        if ((int) $value->format('Y') < 1) {
            return '';
        }
        return $value->format($format);
    }

    protected function getFormerField()
    {
        $input = Former::date($this->getFormerName())
            ->id($this->getFormerId())
            ->value($this->getValue())
            // form-utils.js reads this and hands it to jQuery UI. Forcing dd/mm/yy is
            // required, not cosmetic: everything downstream of the picker (updateOriginal's
            // regex, getPtBrDatetime, getIsoDatetime) parses pt-BR dd/mm/yyyy, while jQuery UI
            // defaults to US mm/dd/yy. Left at the default, a picked/"Atualizar" date arrives
            // as mm/dd/yyyy -- discarded when the day is > 12, and silently day/month-swapped
            // when it is not. Same reason the list filters set it (records/_index/filters.blade.php).
            ->setAttribute('data-datepicker', '{"dateFormat":"dd/mm/yy"}')
            ->append($this->getUpdateButton());
        if ($this->isDatetime()) {
            $input->type('datetime-local');
        }
        return $input;
    }

    public function getMassEditTag()
    {
        $input = $this->getFormerField();
        $this->handleReadonly($input);
        return Element::td((string) $input)->class('date');
    }

    public function hasMassEdit(): bool
    {
        return true;
    }

    protected function getUpdateButton()
    {
        // Must render as a literal <button> tag: Former's TwitterBootstrap5 framework
        // passes an appended item through untouched only when its markup starts with
        // "<button", and wraps anything else in <span class="input-group-text"> -- the
        // grey padded addon that made this button look inset inside its own input group.
        // An <input type="button"> (what this used to be) trips that fallback.
        // The date-update class is the JS hook; see resources/js/partials/init-date.js.
        $button = Element::create('button', 'Atualizar')
            ->class('btn btn-outline-secondary date-update')
            ->setAttribute('type', 'button');
        $this->handleReadonly($button);
        return $button;
    }
}
