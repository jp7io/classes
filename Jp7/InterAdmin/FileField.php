<?php

namespace Jp7\InterAdmin;

/**
 * Handles the url of uploaded files retrieved from the database.
 */
class FileField
{
    use Downloadable;

    protected $_parent;
    /** @var string Creditos/Legenda da imagem. */
    public $text;
    /**
     * Url da imagem.
     *
     * @var
     */
    public $url;

    public function __construct($url, $text = '')
    {
        $this->url = $url;
        $this->text = $text;
    }
    public function __toString(): string
    {
        return $this->url;
    }

    /**
     * Text for the image's "alt" or "title".
     * Uses the file's "Creditos/Leg.:" field, or the record's varchar_key.
     *
     * @return string
     */
    public function getText()
    {
        if ($this->text) {
            return $this->text;
        }
        if ($parent = $this->getParent()) {
            return $parent->getName();
        }

        return '';
    }
    /**
     * @see FileField::$parent
     *
     * @return \InterAdmin\Models\Record|null
     */
    public function getParent()
    {
        return $this->_parent;
    }
    /**
     * Sets $parent.
     *
     * @param \InterAdmin\Models\Record $parent
     *
     * @see FileField::$parent
     */
    public function setParent($parent): void
    {
        $this->_parent = $parent;
    }
}
