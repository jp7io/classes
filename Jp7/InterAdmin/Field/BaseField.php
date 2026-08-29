<?php

namespace Jp7\InterAdmin\Field;

use HtmlObject\Element;
use Former;
use Jp7\InterAdmin\Type;

abstract class BaseField implements FieldInterface
{
    /**
     * @var string  Field identifier
     */
    protected $id;
    /**
     * @var object
     */
    protected $record;
    /**
     * @var Type
     */
    protected $type;
    /**
     * @var int|null
     */
    protected $index = null;

    public function setRecord($record): void
    {
        assert(is_object($record) || is_null($record));
        $this->record = $record;
    }

    public function setType(Type $type)
    {
        $this->type = $type;
    }

    public function setIndex($index): void
    {
        $this->index = $index;
    }

    public function getHeaderTag()
    {
        return Element::th($this->getHeaderHtml())
            ->class($this->id);
    }

    public function getCellTag()
    {
        return Element::td($this->getCellHtml())
            ->class($this->id);
    }

    public function getHeaderHtml()
    {
        return e($this->getLabel());
    }

    public function getCellHtml()
    {
        return nl2br(e($this->getText()));
    }

    /**
     * Return object for <div class="form-group">...</div>
     *
     * @return Element|string
     */
    public function getEditTag()
    {
        return $this->getFormerField()
            ->label($this->getLabel());
    }

    /**
     * @return Element|string
     */
    public function getMassEditTag()
    {
        $input = $this->getFormerField()->raw();
        $this->handleReadonly($input);
        return Element::td($input)->class($this->id);
    }

    /**
     * Lock an input a user may not change. Declared here because getEditTag()/getMassEditTag()
     * call it; only fields that carry a `readonly` or `permissoes` flag have anything to do.
     */
    protected function handleReadonly($input)
    {
    }

    public function getFilterTag()
    {
        return '';
    }

    public function getFilterSql()
    {
        return '';
    }

    public function hasMassEdit()
    {
        return false;
    }

    /**
     * Former field. A Former field has 3 parts: element, label and group.
     * Group and label attributes should be changed on getEditTag().
     *
     * @see https://github.com/formers/former/wiki/Usage-and-Examples
     * @return Former\Traits\Field
     */
    protected function getFormerField()
    {
        return Former::text($this->getFormerName())
            ->id($this->getFormerId())
            ->value($this->getValue());
    }

    /**
     * The record property this field reads and writes.
     */
    protected function getColumn(): string
    {
        return $this->id;
    }

    /**
     * The stem of the field's DOM id, which is the human-readable alias where a field has one.
     */
    protected function getIdentifier(): string
    {
        return $this->id;
    }

    protected function getFormerName(): string
    {
        return $this->getColumn().(is_null($this->index) ? '' : '['.$this->index.']');
    }

    protected function getFormerId(): string
    {
        return $this->getIdentifier().(is_null($this->index) ? '' : '_'.$this->index);
    }

    protected function getRuleName()
    {
        $name = str_replace( // same replace Laravel and Former do
            ['[', ']'],
            ['.', ''],
            $this->getFormerName()
        );
        return trim($name, '.');
    }

    protected function getValue()
    {
        $column = $this->getColumn();
        $value = $this->record ? $this->record->$column : null;
        if (empty($this->record->id) && !$value) {
            $value = $this->getDefaultValue();
        }
        return $value;
    }

    protected function getDefaultValue()
    {
        return null;
    }

    public function getRules()
    {
        return [];
    }

    public function getOrderSql($direction)
    {
        return '';
    }

    public function getSearchSql($search)
    {
        return '';
    }
}
