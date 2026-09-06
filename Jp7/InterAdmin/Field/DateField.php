<?php

namespace Jp7\InterAdmin\Field;

use Illuminate\Support\Str;

class DateField extends ColumnField
{
    use DateFieldTrait;

    const XTRA_NORMAL = '';
    const XTRA_NO_TIME = 'notime';
    protected $id = 'date';

    protected function isDatetime(): bool
    {
        return empty($this->xtra) || // publish_at in some situations
            Str::endsWith($this->xtra, '_datetime') ||
            $this->xtra === self::XTRA_NORMAL;
    }
}
