<?php

namespace Jp7;

/**
 * An array whose missing keys read as null rather than warning. ⚠ It does NOT alias the array it
 * is given: the `&` this constructor carried until 2026-09-15 bound the by-value parameter, so a
 * write here never reached the caller. Every call site (ci's five Busca classes) only reads.
 */
class RiskyArray implements \ArrayAccess
{
    private $container = [];

    public function __construct(?array $array = null)
    {
        if ($array) {
            $this->container = $array;
        }
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->container[$offset]);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->container[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->container[$offset] ?? null;
    }
}
