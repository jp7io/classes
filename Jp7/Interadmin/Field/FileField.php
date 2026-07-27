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

    /**
     * Every other field is rendered by Former as `.mb-3.row` with a `.col-form-label.col-lg-2`
     * label and a `.col-lg-10` field column. This one hand-builds its row because it carries a
     * thumbnail the grid has no slot for -- and it used to build a DIFFERENT row: a bare flex
     * box with no grid gutters and no bottom margin, so a file field sat 12px right of every
     * field above and below it, its label text 20px further right still, with no gap under it.
     *
     * Same grid as everyone else now; the thumbnail rides along inside the field column, which
     * is the one part that stays flex. The classes JS hooks on (.file-field, .input-with-credits,
     * .image_preview_background) are unchanged, as is their nesting relative to each other.
     */
    protected function getFormerField()
    {
        $label = Element::label($this->campo['nome'])->class('col-form-label col-lg-2 col-sm-4');
        $input = parent::getFormerField();
        $input = Element::div()->class('input-group')->nest([$input, $this->getSearchButton()]);
        $inputWithCredits = Element::div()->class('input-with-credits flex-fill d-flex flex-column gap-2')->nest([$input, $this->getCreditsHtml()]);
        $column = Element::div()->class('col-lg-10 col-sm-8 d-flex gap-2')->nest([$inputWithCredits, $this->getCellHtml()]);
        $inputWithPreview = Element::div()->class('file-field mb-3 row')->nest([$label, $column]);
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
