<?php

namespace Jp7\Validator;

/**
 * A phone number, judged on its digits alone.
 *
 * The stored corpus dictates the range, because until now there was no server rule at all and the
 * column holds whatever a public form let through. Across ci's 598 stored `telefone` values the
 * digit count is bimodal with nothing in between: 494 rows at 10-11 digits (Brazil), 51 at 12-15
 * (a country code), 5 at 8-9 -- and then 48 rows at 6 digits or fewer, which are `pg_sleep` /
 * `waitfor delay` scanner payloads, not numbers. Nothing sits at 7, so the cut lands in a real gap.
 *
 * 15 is E.164's maximum; there is no legitimate number above it.
 */
class Telefone
{
    public function validate($attribute, $value, $parameters)
    {
        return (bool) preg_match('/^[0-9]{8,15}$/', preg_replace('/[^0-9]/', '', $value));
    }
}
