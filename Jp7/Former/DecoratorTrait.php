<?php

namespace Jp7\Former;

trait DecoratorTrait
{
    private $decorators = [];

    abstract public function __call($method, $arguments);

    public function decorator(): \Jp7\Former\Decorator
    {
        $decorator = new Decorator();
        $this->decorators[] = $decorator;

        return $decorator;
    }

    public function closeDecorator(): void
    {
        array_pop($this->decorators);
    }

    private function decorateField($field): void
    {
        foreach ($this->decorators as $decorator) {
            $decorator->_runOn($field);
        }
    }
}
