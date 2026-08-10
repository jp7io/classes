<?php

namespace Jp7\Interadmin\Field;

use Former;
use HtmlObject\Element;
use Interadmin\Files\FileOrigin;
use Interadmin\Files\FilePreview;
use Interadmin\Files\StoredPath;

class FileField extends ColumnField
{
    protected $id = 'file';

    public function getCellHtml()
    {
        $preview = FilePreview::render($this->getText() ?: DEFAULT_PATH.'/img/px.png');

        return Element::div((string) $preview);
    }

    /**
     * The one field that builds its own row, because the thumbnail has no slot in Former's grid.
     * Every group concern is therefore this class's to apply, from the helpers the Former path
     * uses -- classes, the label's `for` and title, the help span, readonly and required.
     *
     * .file-field, .input-with-credits and .image_preview_background must keep their nesting:
     * partials/init-file.js walks from the button to the preview, and partials/field-help.js
     * puts the (?) in the thumbnail's corner by the same shape.
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

    /**
     * The input shows the storage key; the `../../` sentinel in front of it is spelled by
     * Interadmin\Files\StoredPath and never by an editor. getValue() itself is left alone --
     * getText() feeds it to the thumbnail, which addresses the file by its stored path.
     * RecordController::prepareFormData() puts the sentinel back on the way in.
     *
     * forceValue(), because value() is documented to lose to the populator and the record IS
     * populated (Jp7\Former\FormerExtension) -- so it left every stored path untouched. Forcing
     * would in turn discard what the user typed after a failed validation, hence the posted
     * value is asked for first; display() is a no-op on one, having no sentinel to strip.
     */
    protected function getFormerField()
    {
        $input = parent::getFormerField();
        $posted = Former::getPost($this->getFormerName());
        $input->forceValue(StoredPath::display($posted ?? $this->getValue()));
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
                ->nest([$this->getOriginHtml(), $this->getFormerField(), $this->getSearchButton()]),
            $this->getCreditsHtml(),
        ];
        if ($this->ajuda) {
            $children[] = Element::span($this->ajuda)->class('form-text');
        }

        return Element::div()
            ->class('input-with-credits flex-fill d-flex flex-column gap-2')
            ->nest($children);
    }

    protected function getOriginHtml()
    {
        return FileOrigin::element($this->getValue());
    }

    protected function getSearchButton()
    {
        // data-field-id rather than data-target, which is Bootstrap's own spelling for a
        // collapse/modal target: this names the input the picker fills. partials/init-file.js.
        $button = Element::create('button', 'Procurar...')
            ->class('btn btn-outline-secondary choose-file')
            ->setAttribute('type', 'button')
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
            Element::span('Legenda')->class('input-group-text'),
            $input,
        ]);
    }
}
