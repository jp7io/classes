<?php
// Necess�rio para herdar m�todos padr�o
return Jp7_Controller_Dispatcher::evalAsAController(__FILE__);

class Jp7_VideosController extends __Controller_Action {
	
	public function indexAction() {
		$contentTipo = self::getTipo();
		
		if ($id = $this->_getParam('id')) {
			$this->record = $contentTipo->getInterAdminById($id, array(
				'fields' => array('*')
			));
			if (!$this->record) {
				$this->_redirect($contentTipo->getUrl());
			}
		} else {
			// Introdu��o
			if ($introductionTipo = $contentTipo->getFirstChildByModel('Introduction')) {
				$this->view->introductionItens = $introductionTipo->getInterAdmins(array(
					'fields' => '*'
				));
			}
			
			$this->view->records = $contentTipo->getInterAdmins(array(
				'fields' => array('*')
			));
		}
	}
}