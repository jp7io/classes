<?php

namespace Jp7\Interadmin\Field;

class TitField extends ColumnField
{
    protected $id = 'tit';

    const XTRA_VISIBLE = '0';
    const XTRA_HIDDEN = 'hidden';

    public function openPanel()
    {
        return '<div class="card card-default '.$this->id.'-panel '.$this->nome_id.'-panel">'.
                    $this->getEditTag().
                    '<div id="'.$this->getPanelId().'" class="collapse'.
                        ($this->isOpen() ? ' show' : '').'">'.
                        '<div class="card-body">';
    }

    /**
     * A real <button>, not an anchor wearing role="button": there is nowhere to navigate to, and
     * only a button announces itself as pressable without being told to. aria-expanded is what
     * says which way the section is currently folded -- Bootstrap keeps it in step from here on.
     */
    public function getEditTag()
    {
        $open = $this->isOpen();

        return '<div class="card-header">'.
            '<h4 class="card-title">'.
                '<button type="button" class="'.($open ? '' : 'collapsed').'"'.
                    ' data-bs-toggle="collapse" data-bs-target="#'.$this->getPanelId().'"'.
                    ' aria-expanded="'.($open ? 'true' : 'false').'"'.
                    ' aria-controls="'.$this->getPanelId().'"'.
                    ' title="'.e($this->tipo).'">'.
                    e($this->getLabel()).
                '</button>'.
            '</h4>'.
        '</div>';
    }

    public function closePanel()
    {
        return '    </div>
                </div>
            </div>';
    }

    protected function isOpen(): bool
    {
        return $this->xtra === self::XTRA_VISIBLE;
    }

    /**
     * The two parts are separated, because concatenating them straight lets tit_1 at index 10 and
     * tit_11 at index 0 land on the same id.
     */
    protected function getPanelId(): string
    {
        return 'collapse-'.$this->tipo.'-'.(int) $this->index;
    }
}
