<?php

class Jp7_Box_NewsArchive extends Jp7_Box_BoxAbstract {    /**
     * @see Jp7_Box_BoxAbstract::prepareData()
     */
    public function prepareData() {
    	/*
    	$newsTipo = InterAdminTipo::findFirstTipoByModel('News');
		if ($newsTipo) {
			$options = array(
				'fields' => array('title', 'image', 'date_publish'),
				'fields_alias' => true, // N�o d� para garantir que est� true por padr�o
				'limit' => $this->params->limit
			);
			if ($this->params->featured) {
				$options['where'][] = "featured <> ''";
			}
			$this->title = ($this->params->title) ? $this->params->title : $newsTipo->getNome();
			$this->news = $newsTipo->getInterAdmins($options);
		} else {
			$this->news = array();	
		}
		*/
    }
    /**
     * @see Jp7_Box_BoxAbstract::_getEditorTitle()
     */
    protected function _getEditorTitle() {
        return 'Hist�rico de Not�cias';
    }

}