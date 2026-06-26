<?php

namespace Jp7\Interadmin\Field;

use Illuminate\Support\Str;

class DateField extends ColumnField
{
    use DateFieldTrait;

    const XTRA_NORMAL = '0';
    const XTRA_NO_TIME = 'S';
    protected $id = 'date';
    /*
    $S_form_xtra_arr[0] = "Normal";
    $S_form_xtra_arr['S']="Sem Hora"; // TODOS ABAIXO MORRERAM!!! ;)
    $S_form_xtra_arr['calendar_datetime'] = "Calendário";
    $S_form_xtra_arr['calendar_date']="Calendário S/ Hora";
    $S_form_xtra_arr['nocombo_datetime']="S/ Combo";
    $S_form_xtra_arr['nocombo_date']="S/ Combo S/ Hora";
    $S_form_xtra_arr['calendar_nocombo_datetime']="Calendário S/ Combo";
    $S_form_xtra_arr['calendar_nocombo_date']="Calendário S/ Combo S/ Hora";
    */

    protected function isDatetime()
    {
        return empty($this->xtra) || // date_publish in some situations
            Str::endsWith($this->xtra, '_datetime') ||
            $this->xtra === self::XTRA_NORMAL;
    }
}
