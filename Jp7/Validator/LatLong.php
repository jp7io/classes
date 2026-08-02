<?php

namespace Jp7\Validator;

/**
 * The `ll` xtra: latitude and longitude in one box, comma-separated.
 *
 * Range-checked rather than pattern-matched, because the mask in partials/init-varchar.js is
 * narrower than what is legitimately storable -- it fixes six decimal places, and a coordinate with
 * three is not wrong. All 44,532 values ci has stored pass either way; they are machine-written
 * (`-23.6060434,-46.6583985`), never typed, so the corpus cannot distinguish the two and the
 * looser rule is the one that will not surprise a human filling the field in by hand.
 *
 * Whitespace is stripped for the same reason: no stored value carries any, but a coordinate pasted
 * from a map has a space after the comma.
 */
class LatLong
{
    public function validate($attribute, $value, $parameters)
    {
        $parts = explode(',', preg_replace('/\s+/', '', $value));

        return count($parts) === 2
            && is_numeric($parts[0]) && is_numeric($parts[1])
            && abs((float) $parts[0]) <= 90 && abs((float) $parts[1]) <= 180;
    }
}
