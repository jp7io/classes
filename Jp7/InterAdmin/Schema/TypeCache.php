<?php

namespace Jp7\InterAdmin\Schema;

/** The cache store every Type's derivations live in, whichever object model derives them. */
final class TypeCache
{
    // ⚠ ONE tag for both Types: FixOrphanType and the MFA migration flush this one, and a
    // second tag is a store none of their flushes reaches.
    public const TAG = 'type';

    /** Seconds, which Cache::remember() has taken since Laravel 5.8, never minutes. */
    public const TTL = 300;
}
