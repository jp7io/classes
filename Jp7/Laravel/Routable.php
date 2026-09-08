<?php

namespace Jp7\Laravel;

trait Routable
{
    public function getChildrenMenu()
    {
        return $this->children()->where('menu', true)->get();
    }
}
