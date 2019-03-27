<?php

namespace Jp7\Former\Fields;

class Radio extends \Former\Form\Fields\Radio
{
  public function radios(...$args)
  {
    $this->items = []; // make it possible to override previous radios
    return parent::radios(...$args);
  }
}
