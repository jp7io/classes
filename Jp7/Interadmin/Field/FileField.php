<?php

namespace Jp7\Interadmin\Field;

use HtmlObject\Element;
use HtmlObject\Input;

class FileField extends ColumnField
{
    protected $id = 'file';

    public function getCellHtml()
    {
        $preview = interadmin_arquivos_preview(
            $this->getText() ?: DEFAULT_PATH.'/img/px.png', // url
            '', // alt
            false, // presrc
            true // icon_small
        );
        return Element::div($preview);
    }

    protected function getFormerField()
    {
        $label = Element::label($this->campo['nome'])->class('control-label col-lg-2 col-sm-4 text-end');
        $input = parent::getFormerField();
        $input = Element::div()->class('input-group')->nest([$input, $this->getSearchButton()]);
        $inputWithCredits = Element::div()->class('input-with-credits w-100 d-flex flex-column gap-2')->nest([$input, $this->getCreditsHtml()]);
        $inputWithPreview = Element::div()->class('file-field d-flex gap-2')->nest([$label, $inputWithCredits, $this->getCellHtml()]);
        return $inputWithPreview;
    }

    protected function getSearchButton()
    {
        $input = Input::button(null, 'Procurar...')
            ->class('btn btn-outline-secondary choose-file')
            ->setAttribute('data-target', $this->nome_id.'_'.$this->index);
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
        return Element::div('
            <span class="input-group-text">Legenda:</span>'.
            $input->raw())->class('input-group');
    }
}
