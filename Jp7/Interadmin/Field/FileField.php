<?php

namespace Jp7\Interadmin\Field;

use HtmlObject\Input;

class FileField extends ColumnField
{
    protected $id = 'file';

    public function getCellHtml()
    {
        return interadmin_arquivos_preview(
            $this->getText() ?: DEFAULT_PATH.'/img/px.png', // url
            '', // alt
            false, // presrc
            true // icon_small
        );
    }

    protected function getFormerField()
    {
        $input = parent::getFormerField();
        $input->append($this->getSearchButton());
        // TODO td.image_preview .image_preview_background
        $input->append($this->getCellHtml()); // thumbnail
        if ($this->xtra !== 'notext') {
            $input->append($this->getCreditsHtml());
        }
        return $input;
    }

    protected function getSearchButton()
    {
        $input = Input::button(null, 'Procurar...')
            ->class('choose-file');
        $this->handleReadonly($input);
        return $input;
    }

    protected function getCreditsHtml()
    {
        $field = new VarcharField(['tipo' => $this->tipo.'_text']);
        $field->setRecord($this->record);
        $field->setIndex($this->index);
        $input = $field->getFormerField();
        $this->handleReadonly($input);
        return '<div class="input-group"><span class="input-group-addon">Legenda:</span>'.
            $input->raw().'</div>';
    }
}
