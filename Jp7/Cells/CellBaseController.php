<?php

namespace Jp7\Cells;

use Illuminate\View\Factory;
use Jp7\Laravel\Controller;

abstract class CellBaseController extends \Torann\Cells\CellBaseController
{
    private $returned;
    /**
     * @var array Variables to send to view
     */
    private $viewData = [];
    public $type;
    public $record;
    public $name;

    public function &__get($key)
    {
        return $this->viewData[$key];
    }

    public function __set($key, $value)
    {
        $this->viewData[$key] = $value;
    }

    // Multiple calls to a cell will run __construct only once
    public function __construct(Factory $view, $caching_disabled)
    {
        parent::__construct($view, $caching_disabled);
        // CoC - name is always snake_case of the class name
        $this->name = snake_case(substr(get_called_class(), 4), '-');

        if (class_exists('Debugbar', false)) {
            \Debugbar::startMeasure('Cell '.$this->name);
        }
    }

    public function setSharedVariables()
    {
        // Current section
        $this->type = Controller::getCurrentController()->type;
        $this->record = Controller::getCurrentController()->record;
    }

    public function init()
    {
        // it makes init optional
    }

    public function initCell($viewAction = 'display')
    {
        $this->viewAction = $viewAction;

        $this->setSharedVariables();
        $this->init();

        $this->returned = $this->$viewAction();

        if (class_exists('Debugbar', false)) {
            \Debugbar::stopMeasure('Cell '.$this->name);
        }
    }

    protected function defaultViewData()
    {
        return [
            'type' => $this->type,
            'record' => $this->record,
            'name' => $this->name,
            'attributes' => $this->attributes,
        ] + $this->attributes;
    }

    protected function viewData()
    {
        return $this->viewData;
    }

    protected function view(array $data = [])
    {
        $data += $this->defaultViewData();
        $this->data = $data;
        return parent::displayView();
    }

    public function displayView()
    {
        if (is_null($this->returned)) {
            if (env('APP_DEBUG')) {
                throw new \UnexpectedValueException('No view returned from '.static::class);
            }
            return $this->view($this->viewData);
        }
        return $this->returned;
    }

    public function isCached()
    {
        return $this->cache && \Cache::has($this->getCacheKey());
    }

    public function getCacheKey()
    {
        $path = "$this->name.$this->viewAction";

        return "Cells.{$path}.{$this->uniqueCacheId}";
    }
}
