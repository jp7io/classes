<?php

class Jp7_Box_PageContent extends Jp7_Box_BoxAbstract {    /**
     * @see Jp7_Box_BoxAbstract::prepareData()
     */
    public function prepareData() {
    	// Esse n�o � um box normal, ele redireciona para o modelo de conte�do
		$modelTipo = $this->view->tipo->getModel();
		if ($modelTipo instanceof Jp7_Model_TipoAbstract) {
			$modelTipo->prepareData($this);
		}
    }
    
	public $id_box = '_content';
	
    /**
     * @see Jp7_Box_BoxAbstract::_getEditorTitle()
     */
    protected function _getEditorTitle() {
        return 'Conte�do da p�gina';
    }
	protected function _getEditorControls($hasFields = true) {
		// n�o tem icones, n�o pode ser deletado	
	}
	protected function _getEditorFields() {
		// Esse n�o � um box normal, ele redireciona para o modelo de conte�do
    	$modelTipo = $this->view->tipo->getModel();
		if ($modelTipo instanceof Jp7_Model_TipoAbstract) {
			return $modelTipo->getEditorFields($this);
		}
    }
	
	public function getEditorStyle() {
		return "
.box-{$this->id_box} {
	background: #EEEEEE;
	cursor: auto;
}
";
	}
}