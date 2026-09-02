<?php

namespace Jp7\InterAdmin\Field;

/**
 * The record's boolean slots, `bool_key` and `bool_<n>`, tinyint(1) since 2026_09_02_000000.
 *
 * ⚠ XTRA_CHECKED stays 'S'. It is the `fields` blob's own flag for "checked by default", i.e.
 * tenant data in the encoding every type shares, and never the column's value.
 */
class BoolField extends CharField
{
    protected $id = 'bool';

    protected const CHECKED_VALUE = 1;
}
