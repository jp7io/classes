<?php
// Necess�rio para herdar m�todos padr�o
return Jp7_Controller_Dispatcher::evalAsAController(__FILE__);

class Jp7_VideosController extends __Controller_Action {
	
	public function indexAction() {
		$id = $this->_getParam('id');
		// Ir� cachear uma p�gina diferente para cada registro
		Jp7_Cache_Output::getInstance()->start((string) $id);
		
		$contentTipo = self::getTipo();
		
		if ($id) {
			$record = $contentTipo->getInterAdminById($id, array(
				'fields' => array('title', 'video', 'summary')
			));
			if (!$record) {
				$this->_redirect($contentTipo->getUrl());
			}
			self::setRecord($record);
		} else {
			// Introdu��o
			if ($introductionTipo = $contentTipo->getFirstChildByModel('Introduction')) {
				$this->view->introductionItens = $introductionTipo->getInterAdmins(array(
					'fields' => '*'
				));
			}
			
			$this->view->records = $contentTipo->getInterAdmins(array(
				'fields' => array('title', 'thumb', 'summary')
			));
		}
	}
}