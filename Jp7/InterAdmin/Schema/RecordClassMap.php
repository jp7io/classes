<?php

namespace Jp7\InterAdmin\Schema;

/** The record class `types.class` binds each type to. */
class RecordClassMap extends BaseClassMap
{
    protected static $instance;

    // Keeps the pre-recase spelling on purpose: this is a live cache key, not a class
    // reference, and changing it orphans every tenant's entry rather than moving it.
    const CACHE_KEY = 'Interadmin.RecordClassMap';
    const CLASS_ATTRIBUTE = 'class';
}
