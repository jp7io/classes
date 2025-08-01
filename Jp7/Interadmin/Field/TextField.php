<?php

namespace Jp7\Interadmin\Field;

use Former;
use HtmlObject\Element;

class TextField extends ColumnField
{
    protected $id = 'text';
    const XTRA_TEXT = '0';
    const XTRA_HTML = 'S';
    const XTRA_HTML_LIGHT = 'html_light';

    public function getText()
    {
        $text = $this->getValue();
        if (in_array($this->xtra, [self::XTRA_HTML, self::XTRA_HTML_LIGHT])) {
            $text = strip_tags($text);
        }
        return $text;
    }

    protected function getFormerField()
    {
        return Former::textarea($this->getFormerName())
            ->id($this->getFormerId())
            ->value($this->getValue())
            ->data_html($this->xtra ?: false)
            ->rows($this->tamanho ?: 5);
    }
    /*
    public function getMassEditTag()
    {
        $text = $this->getText();
        if (mb_strlen($text) > 100) {
            $text = mb_substr($text, 0, 100).'...';
        }
        return Element::td($text);
    }
    */
}
