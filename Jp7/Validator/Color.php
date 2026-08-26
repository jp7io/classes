<?php

namespace Jp7\Validator;

/**
 * A hexadecimal colour: `#RGB` or `#RRGGBB`.
 *
 * The `#` is optional and the 3-digit form is accepted, but neither is corpus-driven: all 11,781
 * valid `cor` values ci has stored are `#RRGGBB`, because the mask in partials/init-varchar.js can
 * only produce that. The leniency is here so the rule judges the colour rather than the typing.
 *
 * That measurement is also the reason the field is still NOT an `<input type="color">`, which the
 * uniform `#RRGGBB` might otherwise seem to license: the native control cannot represent an EMPTY
 * value -- it reports `#000000` -- so every record with the colour left blank would acquire black
 * on its next save.
 */
class Color
{
    public function validate($attribute, $value, $parameters)
    {
        return (bool) preg_match('/^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', trim($value));
    }
}
