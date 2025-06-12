<?php

namespace Jp7\Interadmin\Field;

class TitField extends ColumnField
{
    protected $id = 'tit';

    const XTRA_VISIBLE = '0';
    const XTRA_HIDDEN = 'hidden';

    public function openPanel()
    {
        $class = ($this->xtra === self::XTRA_VISIBLE) ? 'in' : '';
        return '<div class="card card-default '.$this->id.'-panel '.$this->nome_id.'-panel">'.
                    $this->getEditTag().
                    '<div id="collapse'.$this->tipo.$this->index.'" class="panel-collapse collapse '.$class.'" role="tabpanel">
                        <div class="card-body">';
    }

    public function getEditTag()
    {
        $class = ($this->xtra === self::XTRA_VISIBLE) ? '' : 'collapsed';
        return '<div class="card-header">'.
            '<h4 class="card-title">'.
                '<a role="button" class="'.$class.'" data-toggle="collapse" href="#collapse'.$this->tipo.$this->index.'" '.
                    'aria-controls="collapse'.$this->tipo.$this->index.'" title="'.$this->tipo.'">'.
                    $this->getLabel().
                '</a>'.
            '</h4>'.
        '</div>';
    }

    public function closePanel()
    {
        return '    </div>
                </div>
            </div>';
    }
}
