<?php

namespace Jp7\Interadmin\Field;

use HtmlObject\Element;

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
     * The one field that builds its own row, because the thumbnail has no slot in Former's grid.
     *
     * Everything Former would otherwise put on the group is applied here from the same helpers
     * the Former path uses, so a file field is not a second dialect: the group classes, the
     * label's `for` and title, and the help text as the `.form-text` partials/field-help.js
     * looks for. Bypassing Former also means bypassing the readonly and required handling its
     * group does, so both are spelled out below.
     *
     * The classes JS hooks on -- .file-field, .input-with-credits, .image_preview_background --
     * and their nesting relative to each other are load-bearing: partials/init-file.js walks
     * from the button to the preview, and partials/field-help.js keys the corner placement of
     * the (?) button off the same shape.
     */
    public function getEditTag()
    {
        $column = Element::div()
            ->class('col-lg-10 col-sm-8 d-flex gap-2')
            ->nest([$this->getControlsHtml(), $this->getCellHtml()]);

        return Element::div()
            ->class(implode(' ', $this->getRowClasses()))
            ->nest([$this->getLabelHtml(), $column]);
    }

    protected function getFormerField()
    {
        $input = parent::getFormerField();
        $this->handleReadonly($input);
        return $input;
    }

    /**
     * Former marks a required group from the rules the form was opened with, which a row built
     * by hand never passes through -- so `obrigatorio` has to be spelled out to reach the CSS
     * that weights the label.
     */
    protected function getRowClasses(): array
    {
        $classes = array_merge(['file-field', 'mb-3', 'row'], $this->getGroupClasses());
        if ($this->obrigatorio) {
            $classes[] = 'required';
        }
        return $classes;
    }

    protected function getLabelHtml()
    {
        return Element::label($this->getLabel())
            ->class('col-form-label col-lg-2 col-sm-4')
            ->setAttribute('for', $this->getFormerId())
            ->setAttribute('title', $this->getLabelTitle());
    }

    protected function getControlsHtml()
    {
        $children = [
            Element::div()
                ->class('input-group')
                ->nest([$this->getFormerField(), $this->getSearchButton()]),
            $this->getCreditsHtml(),
        ];
        if ($this->ajuda) {
            $children[] = Element::span($this->ajuda)->class('form-text');
        }

        return Element::div()
            ->class('input-with-credits flex-fill d-flex flex-column gap-2')
            ->nest($children);
    }

    protected function getSearchButton()
    {
        // A literal <button>, like the date field's "Atualizar" beside it. An <input type=button>
        // cannot hold markup and is the odd one out among the form's controls.
        $button = Element::create('button', 'Procurar...')
            ->class('btn btn-outline-secondary choose-file')
            ->setAttribute('type', 'button')
            // data-field-id, not the data-target this used to carry: that is Bootstrap's own
            // spelling for a collapse/modal target, and this names the input the picker fills.
            ->setAttribute('data-field-id', $this->getFormerId());
        $this->handleReadonly($button);
        return $button;
    }

    protected function getCreditsHtml()
    {
        $field = new VarcharField([
            'tipo' => $this->tipo.'_text',
            // Without an alias the credits box is id="_0" on every file field, so any type with
            // two of them renders duplicate ids -- 190 of ci's types do.
            'nome_id' => $this->nome_id.'_text',
        ]);
        $field->setRecord($this->record);
        $field->setIndex($this->index);
        $input = $field->getFormerField();
        $this->handleReadonly($input);

        return Element::div()->class('input-group')->nest([
            Element::span('Legenda:')->class('input-group-text'),
            $input,
        ]);
    }
}
